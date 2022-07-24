<?php

namespace App\Admin\Controllers;

use App\Models\Tour;
use App\Models\Song;
use App\Models\Bio;
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
        $grid->column('tour_id', __('ツアーID'));
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
        $show->field('tour_id', __('ツアーID'));
        $show->field('tour_title', __('ツアータイトル'));
        $show->field('date1', __('開始日'));
        $show->field('date2', __('終了日'));
        $show->field('year', __('年'));
        $show->field('setlist1', __('セットリスト1'))->unescape()->as(function ($setlist1) {
            $result1 = [];
            foreach($setlist1 as $data1) {
                $result1[] = $data1['#'].'. '.$data1['song'];
            }
            return implode('<br>', $result1);
        });
        $show->field('setlist2', __('セットリスト2'))->unescape()->as(function ($setlist2) {
            $result2 = [];
            foreach((array)$setlist2 as $data2) {
                $result2[] = $data2['#'].'. '.$data2['song'];
            }
            return implode('<br>', $result2);
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
        $form = new Form(new Tour());

        $form->tab('データ',function($form) {
            $form->text('tour_title', __('ツアータイトル'));
            $form->text('tour_id', __('ツアーID'));
            $form->radio('tour','ライブ形態')
            ->options([
                0 =>'単発ライブ',
                1 =>'ツアー',
            ])->when(0, function (Form $form) {

                $form->date('date1', __('開催日'))->default(date('Y-m-d'));

            })->when(1, function (Form $form) {

                $form->date('date1', __('開始日'))->default(date('Y-m-d'));
                $form->date('date2', __('終了日'))->default(date('Y-m-d'));
            });
            $form->multipleSelect('year', __('年'))->options(Bio::pluck('year', 'year'));
        })->tab('セットリスト1',function($form) {
            $form->table('setlist1', __('セットリスト1'), function ($table) {
                $table->select('id', __('ID'))->options(Song::all()->pluck('title', 'id'));
                $table->select('song', __('楽曲'))->options(Song::all()->pluck('title', 'title'));
                $table->number('#');
                $table->text('exception', __('例外'));
            });
        })->tab('セットリスト2',function($form) {
            $form->table('setlist2', __('セットリスト2'), function ($table) {
                $table->select('id', __('ID'))->options(Song::all()->pluck('title', 'id'));
                $table->select('song', __('楽曲'))->options(Song::all()->pluck('title', 'title'));
                $table->number('#');
                $table->text('exception', __('例外'));
            });
        })->tab('コメント',function($form) {
            $form->textarea('schedule', __('スケジュール'));
            $form->textarea('text', __('コメント'));
        });

        return $form;
    }
}
