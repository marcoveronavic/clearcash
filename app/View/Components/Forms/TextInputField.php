<?php

namespace App\View\Components\Forms;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TextInputField extends Component
{
    public $label;
    public $name;
    public $type;
    public $required;
    public $value;
    /**
     * Create a new component instance.
     */
    public function __construct($label, $name, $type='text', $required = false, $value = null)
    {
        $this->label = $label;
        $this->name = $name;
        $this->type = $type;
        $this->required = $required;
        $this->value = $value;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.forms.text-input-field');
    }
}
