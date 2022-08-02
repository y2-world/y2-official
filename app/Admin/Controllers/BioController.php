<?php

namespace App\Admin\Controllers;

use App\Models\Bio;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class BioController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Bio';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Bio());

        $grid->column('id', __('ID'));
        $grid->column('year', __('年'));
        // $grid->column('created_at', __('作成日時'));
        // $grid->column('updated_at', __('更新日時'));

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
        $show = new Show(Bio::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('year', __('年'));
        $show->field('text', __('コメント'));
        $show->field('created_at', __('作成日時'));
        $show->field('updated_at', __('更新日時'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Bio());

        $form->text('year', __('年'));
        $form->textarea('text', __('コメント'));

        return $form;
    }
}
