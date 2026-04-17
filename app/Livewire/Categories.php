<?php

namespace App\Livewire;

use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Categories extends Component
{
    public $name = "";
    public $color = "#3B82F6";
    public $icon = "";
    public $editingId = null;
    public $isEditing = false;

    public $colors = [
    	'#EF4444', // Red
    	'#F97316', // Orange
    	'#F59E0B', // Amber
    	'#EAB308', // Yellow
    	'#84CC16', // Lime
    	'#22C55E', // Green
    	'#10B981', // Emerald
    	'#14B8A6', // Teal
    	'#06B6D4', // Cyan
    	'#0EA5E9', // Sky
    	'#3B82F6', // Blue
    	'#6366F1', // Indigo
    	'#8B5CF6', // Violet
    	'#A855F7', // Purple
    	'#D946EF', // Fuchsia
    	'#EC4899', // Pink
    	'#F43F5E', // Rose
    ];

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:categories,name,' . ($this->editingId ?: 'NULL') . ',id,user_id,' . Auth::id(),
            'color' => 'required|string',
            'icon' => 'nullable|string|max:255',
        ];
    }

    protected $messages = [
        'name.required' => 'The category name is required.',
        'name.string' => 'The category name must be a string.',
        'name.max' => 'The category name may not be greater than 255 characters.',
        'name.unique' => 'You already have a category with this name.',
        'color.required' => 'The color is required.',
        'color.string' => 'The color must be a string.',
        'icon.string' => 'The icon must be a string.',
        'icon.max' => 'The icon may not be greater than 255 characters.',
    ];

    // Use computed properties here was used for better optimization for performance
    #[Computed]
    public function categories()
    {
        return Category::withCount('expenses')
            ->where('user_id', Auth::id())
            ->orderBy('name')
            ->get();
    }

    // public function categories()
    // {
    //     return Category::withCount('expenses')
    //     ->where('user_id', auth()->user()->id)
    //     ->orderBy('name')
    //     ->get();
    // }
    public function edit($id)
    {
        $category = Category::findOrFail($id);
        if($category->user_id !== Auth::id()) {
            abort(403);
        }
        $this->name = $category->name;
        $this->color = $category->color;
        $this->icon = $category->icon;
        $this->editingId = $id;
        $this->isEditing = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->isEditing) {
            $category = Category::findOrFail($this->editingId);
            if($category->user_id !== Auth::id()) {
                abort(403);
            }
            $category->update([
                'name' => $this->name,
                'color' => $this->color,
                'icon' => $this->icon,
            ]);

            session()->flash('message', 'Category updated successfully!');

        } else {
            Category::create([
                'name' => $this->name,
                'color' => $this->color,
                'icon' => $this->icon,
                'user_id' => Auth::id(),
                ]);

                session()->flash('message', 'Category created successfully!');
        }


        $this->reset('name', 'color', 'icon', 'editingId', 'isEditing');

    }

    public function cancelEdit()
    {
        $this->reset('name', 'color', 'icon', 'editingId', 'isEditing');
        $this->color = "#3B82F6";// Reset to default color when canceling edit
    }

    public function delete($categoryId)
    {
        $category = Category::findOrFail($categoryId);

        if($category->user_id !== Auth::id()) {
            abort(403);
        }

        if($category->expenses()->count() > 0) {
            session()->flash('message', 'Cannot delete category with associated expenses. Please delete the expenses first.');
            return;
        }

        $category->delete();

        session()->flash('message', 'Category deleted successfully!');
    }

    public function render()
    {
        return view('livewire.categories', [
            'categories' => $this->categories,
        ]);
    }
}
