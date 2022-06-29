<?php

namespace App\Admin\Controllers;

use App\Models\Album;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class AlbumController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Albums';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Album());

        $grid->column('id', __('ID'));
        $grid->column('album_id', __('Album ID'));
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
        $show = new Show(Album::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('album_id', __('Album ID'));
        $show->field('title', __('タイトル'));
        $show->field('date', __('リリース日'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Album());

        $form->text('id', __('ID'))->rules('required');
        $form->text('album_id', __('Album ID'));
        $form->text('title', __('タイトル'))->rules('required');
        $form->date('date', __('リリース日'));

        return $form;
    }
}
