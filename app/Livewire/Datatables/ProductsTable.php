<?php

namespace App\Livewire\Datatables;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Validation\Rule;

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
        $this->editingProduct = $product->id;
        $this->form = $product->toArray();
        $this->form['imagenes'] = $product->imagenes ?? [];
        $this->showModal = true;
    }

    public function save()
    {
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

        // Ajuste de nombres de tabla: Product model usa tabla 'productos'
        if ($this->editingProduct) {
            $product = Product::find($this->editingProduct);
            $product->update($data);
        } else {
            Product::create($data);
        }

        $this->showModal = false;
        $this->editingProduct = null;
        $this->dispatch('product-saved');
        $this->resetPage();
    }

    public function delete(Product $product)
    {
        $product->delete();
        $this->resetPage();
    }

    public function toggleActive(Product $product)
    {
        $product->activo = !$product->activo;
        $product->save();
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

