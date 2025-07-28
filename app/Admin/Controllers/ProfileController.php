<?php

namespace App\Admin\Controllers;

use App\Models\Profile;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ProfileController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Profile';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Profile());

        $grid->column('name', __('名前'));
        $grid->column('info', __('情報'));

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
        $show = new Show(Profile::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('名前'));
        $show->field('info', __('情報'));
        $show->field('text', __('テキスト'));
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
        $form = new Form(new Profile());
        $form->saved(function (Form $form) {
            admin_toastr('保存しました！', 'success');
            return redirect(admin_url('profiles'));
        });

        $form->text('name', __('名前'));
        $form->text('info', __('情報'));
        $form->textarea('text', __('テキスト'));
        $form->image('image', __('画像'))->uniqueName();

        return $form;
    }
}
