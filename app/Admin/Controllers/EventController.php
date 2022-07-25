<?php

namespace App\Admin\Controllers;

use App\Models\Event;
use App\Models\Song;
use App\Models\Bio;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class EventController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Event';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Event());

        $grid->column('id', __('ID'));
        $grid->column('title', __('タイトル'));
        $grid->column('date1', __('開始日'));
        $grid->column('date2', __('終了日'));
        $grid->column('venue', __('会場'));

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
        $show = new Show(Event::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('title', __('タイトル'));
        $show->field('date1', __('開始日'));
        $show->field('date2', __('終了日'));
        $show->field('year', __('年'));
        $show->field('venue', __('会場'));
        $show->field('setlist', __('セットリスト'))->unescape()->as(function ($setlist) {
            $result = [];
            foreach($setlist as $data) {
                $result[] = $data['#'].'. '.$data['song'];
            }
            return implode('<br>', $result);
        });
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
        $form = new Form(new Event());

        $form->tab('データ',function($form) {
            $form->text('title', __('タイトル'))->rules('required');
            $form->date('date1', __('開始日'))->default(date('Y-m-d'))->rules('required');
            $form->date('date2', __('終了日'))->default(date('Y-m-d'));
            $form->multipleSelect('year', __('年'))->options(Bio::pluck('year', 'year'));
            $form->text('venue', __('会場'))->rules('required');
        })->tab('セットリスト',function($form) {
            $form->table('setlist', __('セットリスト'), function ($table) {
                $table->select('id', __('ID'))->options(Song::all()->pluck('title', 'id'));
                $table->select('song', __('楽曲'))->options(Song::all()->pluck('title', 'title'));
                $table->number('#');
                $table->text('exception', __('例外'));
            });
        })->tab('コメント',function($form) {
            $form->textarea('text', __('コメント'));
        });

        return $form;
    }
}
