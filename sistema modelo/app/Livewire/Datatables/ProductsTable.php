<?php

namespace App\Livewire\Datatables;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Support\Str;

// Componente Livewire para gestionar productos (tabla, filtros, modal CRUD)
class ProductsTable extends Component
{
    use WithPagination;

    // Búsqueda y filtros
    public $search = "";
    public $categoryFilter = "";
    public $brandFilter = "";

    // Ordenamiento
    public $sortField = 'nombre';
    public $sortDirection = 'asc';

    // Modal y edición
    public $mostrarModal = false;
    public $editingProduct = null; // id del producto en edición
    public $form = [];

    protected $queryString = ['search', 'categoryFilter', 'brandFilter', 'sortField', 'sortDirection'];

    protected $listeners = ['product-saved' => '$refresh'];

    protected function rules()
    {
        $uniqueCodigo = $this->editingProduct ? 'unique:products,codigo,' . $this->editingProduct : 'unique:products,codigo';

        return [
            'form.codigo' => ['required', 'string', 'max:30', $uniqueCodigo],
            'form.nombre' => ['required', 'string', 'max:200'],
            'form.slug' => ['nullable', 'string', 'max:200'],
            'form.descripcion' => ['nullable', 'string'],
            'form.marca_id' => ['nullable', 'exists:brands,id'],
            'form.categoria_id' => ['nullable', 'exists:categories,id'],
            'form.precio_detal' => ['required', 'numeric'],
            'form.precio_mayor' => ['nullable', 'numeric'],
            'form.precio_costo' => ['nullable', 'numeric'],
            'form.min_cantidad_mayor' => ['nullable', 'integer'],
            'form.stock' => ['nullable', 'integer'],
            'form.stock_minimo' => ['nullable', 'integer'],
            'form.imagenes' => ['nullable', 'array'],
            'form.activo' => ['boolean'],
            'form.destacado' => ['boolean'],
        ];
    }

    // Construye la query aplicada por los filtros y búsqueda
    public function getProductsProperty()
    {
        $query = Product::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('codigo', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->categoryFilter) {
            $query->where('categoria_id', $this->categoryFilter);
        }

        if ($this->brandFilter) {
            $query->where('marca_id', $this->brandFilter);
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    // Abre modal para crear
    public function abrirCrear()
    {
        $this->reset(['form', 'editingProduct']);
        $this->form = [
            'codigo' => '',
            'nombre' => '',
            'slug' => '',
            'descripcion' => '',
            'marca_id' => null,
            'categoria_id' => null,
            'precio_detal' => 0.00,
            'precio_mayor' => 0.00,
            'precio_costo' => 0.00,
            'min_cantidad_mayor' => 10,
            'stock' => 0,
            'stock_minimo' => 5,
            'imagenes' => [],
            'activo' => true,
            'destacado' => false,
        ];
        $this->mostrarModal = true;
    }

    // Abre modal para editar (recibe id)
    public function abrirEditar($id)
    {
        $product = Product::findOrFail($id);
        $this->editingProduct = $product->id;
        $this->form = $product->toArray();
        $this->mostrarModal = true;
    }

    // Guarda (create/update)
    public function save()
    {
        $this->validate($this->rules());

        $data = $this->form;
        $data['slug'] = $data['slug'] ?? Str::slug($data['nombre']);

        if ($this->editingProduct) {
            $product = Product::findOrFail($this->editingProduct);
            $product->update($data);
        } else {
            $product = Product::create($data);
        }

        $this->mostrarModal = false;
        $this->emit('product-saved');
        session()->flash('success', 'Product saved.');
        $this->resetPage();
    }

    // Elimina (soft delete)
    public function delete($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        session()->flash('success', 'Product deleted.');
        $this->resetPage();
    }

    // Toggle activo
    public function toggleActivo($id)
    {
        $product = Product::findOrFail($id);
        $product->activo = !$product->activo;
        $product->save();
    }

    // Ordenamiento
    public function ordenarPor($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    // Métodos auxiliares para stock
    public function addQty($id)
    {
        $product = Product::find($id);
        $product->stock += 1;
        $product->save();
        $this->emit('product-saved');
    }

    public function subQty($id)
    {
        $product = Product::find($id);
        if ($product->stock > 0) {
            $product->stock -= 1;
            $product->save();
        }
        $this->emit('product-saved');
    }

    public function render()
    {
        $products = $this->getProductsProperty()->paginate(10);
        $categories = Category::orderBy('nombre')->get();
        $brands = Brand::orderBy('nombre')->get();

        return view('livewire.datatables.products-table', compact('products', 'categories', 'brands'));
    }
}
