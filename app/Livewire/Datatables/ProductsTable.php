<?php

namespace App\Livewire\Datatables;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\ServicioAuditoria;

/**
 * Livewire component for products datatable.
 * Comentarios en español.
 */
class ProductsTable extends Component
{
    use WithPagination;

    public $search = '';
    public $categoryFilter = '';
    public $brandFilter = '';
    public $sortField = 'nombre';
    public $sortDirection = 'asc';
    public $showModal = false;
    public $editingProduct = null;
    public $form = [];

    protected $listeners = ['product-saved' => '$refresh'];

    // Computed property: $this->products
    public function getProductsProperty()
    {
        $query = Product::query()->with(['brand', 'category']);

        if ($this->search) {
            $q = $this->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre', 'like', "%{$q}%")
                    ->orWhere('codigo', 'like', "%{$q}%");
            });
        }

        if ($this->categoryFilter) {
            $query->where('categoria_id', $this->categoryFilter);
        }

        if ($this->brandFilter) {
            $query->where('marca_id', $this->brandFilter);
        }

        $allowed = ['nombre', 'precio_detal', 'precio_mayor', 'stock', 'codigo'];
        $field = in_array($this->sortField, $allowed) ? $this->sortField : 'nombre';

        $query->orderBy($field, $this->sortDirection);

        return $query->paginate(10);
    }

    public function render()
    {
        $categories = Category::orderBy('nombre')->get();
        $brands = Brand::orderBy('nombre')->get();

        return view('livewire.datatables.products-table', [
            'products' => $this->products,
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    /**
     * Abrir modal para crear nuevo producto.
     */
    public function openCreate()
    {
        Gate::authorize('productos.editar');

        $this->reset(['form', 'editingProduct']);
        $this->form = [
            'codigo' => '',
            'nombre' => '',
            'slug' => '',
            'descripcion' => '',
            'marca_id' => '',
            'categoria_id' => '',
            'precio_detal' => 0,
            'precio_mayor' => 0,
            'precio_costo' => null,
            'min_cantidad_mayor' => 10,
            'stock' => 0,
            'stock_minimo' => 5,
            'imagenes' => [],
            'activo' => true,
            'destacado' => false,
        ];
        $this->showModal = true;
    }

    /**
     * Abrir modal para editar producto.
     */
    public function openEdit(Product $product)
    {
        Gate::authorize('productos.editar');

        $this->editingProduct = $product->id;
        $this->form = $product->toArray();
        $this->form['imagenes'] = $product->imagenes ?? [];
        $this->showModal = true;
    }

    public function save()
    {
        Gate::authorize('productos.editar');

        $rules = [
            'form.codigo' => ['required', 'string', 'max:30', Rule::unique('products', 'codigo')->ignore($this->editingProduct)],
            'form.nombre' => 'required|string|max:200',
            'form.slug' => ['nullable', 'string', 'max:200', Rule::unique('products', 'slug')->ignore($this->editingProduct)],
            'form.descripcion' => 'nullable|string',
            'form.marca_id' => 'required|exists:marcas,id',
            'form.categoria_id' => 'required|exists:categorias,id',
            'form.precio_detal' => 'required|numeric|min:0',
            'form.precio_mayor' => 'required|numeric|min:0',
            'form.precio_costo' => 'nullable|numeric|min:0',
            'form.min_cantidad_mayor' => 'integer|min:1',
            'form.stock' => 'integer|min:0',
            'form.stock_minimo' => 'integer|min:0',
            'form.imagenes' => 'nullable|array',
            'form.activo' => 'boolean',
            'form.destacado' => 'boolean',
        ];

        $this->validate($rules);

        $data = $this->form;
        $data['activo'] = (bool) ($data['activo'] ?? true);
        $data['destacado'] = (bool) ($data['destacado'] ?? false);

        DB::transaction(function () use ($data) {
            $actorId = Auth::id();
            if ($this->editingProduct) {
                $product = Product::where('id', $this->editingProduct)->lockForUpdate()->first();
                if ($product) {
                    $antes = $product->only(['codigo', 'nombre', 'precio_detal', 'precio_mayor', 'stock', 'activo']);
                    $product->update($data);
                    $despues = $product->only(['codigo', 'nombre', 'precio_detal', 'precio_mayor', 'stock', 'activo']);
                    ServicioAuditoria::registrar('producto.modificado', $product, $antes, $despues, $actorId);
                }
            } else {
                $product = Product::create($data);
                $despues = $product->only(['codigo', 'nombre', 'precio_detal', 'precio_mayor', 'stock', 'activo']);
                ServicioAuditoria::registrar('producto.creado', $product, null, $despues, $actorId);
            }
        });

        $this->showModal = false;
        $this->editingProduct = null;
        $this->dispatch('product-saved');
        $this->resetPage();
    }

    public function delete(Product $product)
    {
        Gate::authorize('productos.editar');

        DB::transaction(function () use ($product) {
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->first() ?? $product;
            $antes = ['deleted_at' => null];
            $lockedProduct->delete();
            $despues = ['deleted_at' => (string)now()];
            ServicioAuditoria::registrar('producto.eliminado', $lockedProduct, $antes, $despues, Auth::id());
        });

        $this->resetPage();
    }

    public function toggleActive(Product $product)
    {
        Gate::authorize('productos.editar');

        DB::transaction(function () use ($product) {
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->first() ?? $product;
            $antes = ['activo' => $lockedProduct->activo];
            $lockedProduct->activo = !$lockedProduct->activo;
            $lockedProduct->save();
            $despues = ['activo' => $lockedProduct->activo];
            ServicioAuditoria::registrar('producto.estado_cambiado', $lockedProduct, $antes, $despues, Auth::id());
        });

        $this->dispatch('product-saved');
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
}

