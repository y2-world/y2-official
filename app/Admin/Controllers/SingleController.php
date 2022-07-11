<?php

namespace App\Admin\Controllers;

use App\Models\Single;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class SingleController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Single';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Single());

        $grid->column('id', __('Id'));
        $grid->column('single_id', __('Single ID'));
        $grid->column('title', __('タイトル'));
        $grid->column('date', __('リリース日'));

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Single::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('title', __('Title'));
        $show->field('date', __('Date'));
        $show->field('single_id', __('Single id'));
        $show->field('text', __('Text'));
        $show->field('created_at', __('作成日'));
        $show->field('updated_at', __('更新日'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Single());

        $form->text('id', __('ID'))->rules('required');
        $form->text('single_id', __('Single ID'));
        $form->text('title', __('タイトル'))->rules('required');
        $form->date('date', __('リリース日'));
        $form->textarea('text', __('コメント'));

        return $form;
    }
}
