<?php

namespace App\Admin\Controllers;

use App\Artist;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ArtistController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Artist';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Artist());

        $grid->column('id', __('ID'));
        $grid->column('name', __('アーティスト名'));
        $grid->column('created_at', __('作成日'));
        $grid->column('updated_at', __('更新日'));

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
        $show = new Show(Artist::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('アーティスト名'));
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
        $form = new Form(new Artist());

        $form->text('name', __('アーティスト名'));

        return $form;
    }
}
