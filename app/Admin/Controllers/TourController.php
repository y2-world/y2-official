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
        $grid->column('title', __('ツアータイトル'));
        $grid->column('date1', __('開始日'));
        $grid->column('date2', __('終了日'));

        $grid->filter(function($filter){
            $filter->equal('type')->radio([
                ''   => 'All',
                0    => 'ツアー',
                1    => '単発ライブ',
                2    => 'イベント',
                3    => 'ap bank fes',
            ]);
            
            $filter->year('year', '年');
        });

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
        $show->field('type', __('タイプ'));
        $show->field('title', __('タイトル'));
        $show->field('tour_id', __('ツアーID'));
        $show->field('event_id', __('イベントID'));
        $show->field('ap_id', __('ap ID'));
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
            $form->text('title', __('タイトル'));
            $form->radio('type','ライブタイプ')
            ->options([
                0 =>'ツアー',
                1 =>'単発ライブ',
                2 =>'イベント',
                3 =>'ap bank fes',
            ])->when(0, function (Form $form) {
                $form->text('tour_id', __('ID'));
                $form->dateRange('date1', 'date2', '開催期間');
            })->when(1, function (Form $form) {
                $form->dateRange('date1', 'date2', '開催期間');
            })->when(2, function (Form $form) {
                $form->text('event_id', __('ID'));
                $form->date('date1', __('開催日'))->default(date('Y-m-d'));
            })->when(3, function (Form $form) {
                $form->text('ap_id', __('ID'));
                $form->dateRange('date1', 'date2', '開催期間');
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
