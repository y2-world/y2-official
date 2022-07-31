<?php

namespace App\Admin\Controllers;

use App\Models\Radio;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class RadioController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Radio';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Radio());

        $grid->column('id', __('ID'));
        $grid->column('title', __('タイトル'));
        $grid->column('date', __('公開日'));    

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
        $show = new Show(Radio::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('title', __('タイトル'));
        $show->field('date', __('日付'));
        $show->field('text', __('公開日'));
        $show->field('image', __('画像'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Radio());

        $form->text('title', __('タイトル'));
        $form->date('date', __('日付'));
        $form->textarea('text', __('テキスト'));
        $form->image('image', __('画像'))->move('public/images/');

        return $form;
    }
}
