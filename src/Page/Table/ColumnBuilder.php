<?php

namespace Ignite\Page\Table;

use Ignite\Contracts\Page\Column as ColumnContract;
use Ignite\Contracts\Page\ColumnBuilder as ColumnBuilderContract;
use Ignite\Crud\Fields\Relations\LaravelRelationField;
use Ignite\Page\Actions\DropdownItemAction;
use Ignite\Page\Actions\TableButtonAction;
use Ignite\Page\Table\Casts\CarbonColumn;
use Ignite\Page\Table\Casts\MoneyColumn;
use Ignite\Page\Table\Components\BladeColumnComponent;
use Ignite\Page\Table\Components\ColumnComponent;
use Ignite\Page\Table\Components\DropdownComponent;
use Ignite\Page\Table\Components\ImageComponent;
use Ignite\Page\Table\Components\ProgressComponent;
use Ignite\Page\Table\Components\RelationComponent;
use Ignite\Page\Table\Components\ToggleComponent;
use Ignite\Support\VueProp;
use Ignite\Vue\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFactory;

class ColumnBuilder extends VueProp implements ColumnBuilderContract
{
    /**
     * Table column stack.
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Parent instance.
     *
     * @var Table|LaravelRelationField|null
     */
    protected $parent;

    /**
     * Set table instance.
     *
     * @param  Table|LaravelRelationField $parent
     * @return void
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * Gets parent.
     *
     * @return Table|LaravelRelationField|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Get column stack.
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Add column.
     *
     * @param  string $label
     * @return Column
     */
    public function col($label = ''): ColumnContract
    {
        return $this->columns[] = new Column($label);
    }

    /**
     * Create new action column.
     *
     * @param  string    $title
     * @param  string    $action
     * @return Component
     */
    public function action($title, $action): ColumnContract
    {
        $this->columns[] = $component = (new TableButtonAction)->make($title, $action);

        return $component->getProp('wrapper')->link(false);
    }

    /**
     * Create new actions column.
     *
     * @param  string    $title
     * @param  string    $action
     * @return Component
     */
    public function actions($actions, $text = '<i class="fas fa-ellipsis-v"></i>'): ColumnContract
    {
        $this->columns[] = $dropdown = (new DropdownComponent);

        foreach ($actions as $title => $action) {
            $action = (new DropdownItemAction)->make($title, $action);

            $dropdown->item($action);
        }

        return $dropdown->link(false)
            ->size('sm')
            ->text($text)
            ->noCaret()
            ->variant('transparent')
            ->class('dropdown-sm-square')
            ->small();
    }

    /**
     * Create new Money column.
     *
     * @param  string $column
     * @param  string $currency
     * @return Column
     */
    public function money($column, $currency = 'EUR', $locale = null): ColumnContract
    {
        if ($this->parent) {
            $this->parent->cast(
                $column, MoneyColumn::class.":{$currency},{$locale}"
            );
        }

        return $this->col(ucfirst($column))
            ->class('lit-col-money')
            ->value("{{$column}}")
            ->sortBy($column)
            ->right();
    }

    /**
     * Add table column to cols stack and set component.
     *
     * @param  string          $component
     * @return ColumnComponent
     */
    public function component($component): ColumnContract
    {
        return $this->columns[] = component($component, ColumnComponent::class);
    }

    /**
     * Add Blade View column.
     *
     * @param  View|string $view
     * @return View
     */
    public function view($view): View
    {
        if (! $view instanceof View) {
            $view = ViewFactory::make($view);
        }

        $this->component(new BladeColumnComponent('lit-blade'))->prop('view', $view);

        return $view;
    }

    /**
     * Add livewire component column.
     *
     * @param  string $component
     * @param  array  $data
     * @return View
     */
    public function livewire($component, $data = [])
    {
        return $this->view('litstack::partials.livewire', [
            'component' => $component,
            'data'      => $data,
        ]);
    }

    /**
     * Add progress column.
     *
     * @param  string            $attribute
     * @param  int               $max
     * @return ProgressComponent
     */
    public function progress($attribute, $max = 100)
    {
        return $this->component(new ProgressComponent('lit-col-progress'))
            ->value("{{$attribute}}")
            ->variant('primary')
            ->max($max);
    }

    public function date($attribute, $format)
    {
        if ($this->parent) {
            $this->parent->cast(
                $attribute,
                CarbonColumn::class.":{$format}"
            );
        }

        return $this->col(ucfirst($attribute))
            ->class('lit-col-money')
            ->value("{{$attribute}}")
            ->sortBy($attribute);
    }

    /**
     * Add toggle column.
     *
     * @param  string          $key
     * @return ToggleComponent
     */
    public function toggle($attribute)
    {
        return $this->component(new ToggleComponent('lit-col-toggle'))
            ->prop('link', false)
            ->prop('local_key', $attribute);
    }

    /**
     * Add image column.
     *
     * @param  string         $label
     * @return ImageComponent
     */
    public function image($label = '')
    {
        return $this->component(new ImageComponent('lit-col-image'))->label($label);
    }

    /**
     * Add avatar image column.
     *
     * @param  string         $label
     * @return ImageComponent
     */
    public function avatar($label = '')
    {
        return $this->image($label)->circle();
    }

    /**
     * Add relation column.
     *
     * @param  string            $label
     * @return RelationComponent
     */
    public function relation($label = '')
    {
        return $this->component(new RelationComponent('lit-col-crud-relation'))->prop('label', $label);
    }

    /**
     * Render Builder.
     *
     * @return array
     */
    public function render(): array
    {
        return $this->columns;
    }
}
