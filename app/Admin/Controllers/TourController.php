<?php

namespace App\Admin\Controllers;

use App\Models\Tour;
use App\Models\Song;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TourController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Tour';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Tour());

        $grid->column('id', __('ID'));
        $grid->column('tour_title', __('ツアータイトル'));
        $grid->column('date1', __('開始日'));
        $grid->column('date2', __('終了日'));

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
        $show = new Show(Tour::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('tour_title', __('ツアータイトル'));
        $show->field('date1', __('開始日'));
        $show->field('date2', __('終了日'));
        $show->field('year', __('年'));
        $show->field('setlist', __('本編'));
        $show->field('encore', __('アンコール'));
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
        $form = new Form(new Tour());

        $form->text('tour_title', __('ツアータイトル'));
        $form->date('date1', __('開始日'))->default(date('Y-m-d'));
        $form->date('date2', __('終了日'))->default(date('Y-m-d'));
        $form->text('year', __('年'));
        $form->multipleSelect('setlist', __('本編'))->options(Song::all()->pluck('title', 'id'));
        $form->multipleSelect('encore', __('アンコール'))->options(Song::all()->pluck('title', 'id'));
        $form->textarea('text', __('コメント'));

        return $form;
    }
}
