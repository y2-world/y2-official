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
        $grid->model()->orderBy('date1', 'desc');

        $grid->column('title', __('ツアータイトル'));
        $grid->column('date1', __('開始日'));
        $grid->column('date2', __('終了日'));

         // 年リストを作成（例：2000年〜今年まで）
        $years = range(date('Y'), 2000);
        $years = array_combine($years, $years); // [2025 => 2025, 2024 => 2024, ...]

        $grid->filter(function ($filter) use ($years) {
            $filter->equal('type')->radio([
                ''   => 'All',
                0    => 'ツアー',
                1    => '単発ライブ',
                2    => 'イベント',
                3    => 'ap bank fes',
                4    => 'ソロ',
            ]);

            // 年セレクトフィルター追加
            $filter->where(function ($query) {
                $query->whereYear('date1', $this->input);
            }, '年（西暦）')->select($years);
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
        $show->field('solo_id', __('ソロ ID'));
        $show->field('date1', __('開始日'));
        $show->field('date2', __('終了日'));
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

        $form->tab('データ', function ($form) {
            $form->text('title', __('タイトル'));
            $form->radio('type', 'ライブタイプ')
                ->options([
                    0 => 'ツアー',
                    1 => '単発ライブ',
                    2 => 'イベント',
                    3 => 'ap bank fes',
                    4 => 'ソロ',
                ])->when(0, function (Form $form) {
                    $form->text('tour_id', __('ID'));
                    $form->dateRange('date1', 'date2', '開催期間');
                })->when(1, function (Form $form) {
                    $form->dateRange('date1', 'date2', '開催期間');
                    $form->text('venue', __('会場'));
                })->when(2, function (Form $form) {
                    $form->text('event_id', __('ID'));
                    $form->dateRange('date1', 'date2', '開催期間');
                    $form->text('venue', __('会場'));
                })->when(3, function (Form $form) {
                    $form->text('ap_id', __('ID'));
                    $form->dateRange('date1', 'date2', '開催期間');
                    $form->text('venue', __('会場'));
                })->when(4, function (Form $form) {
                    $form->text('solo_id', __('ID'));
                    $form->dateRange('date1', 'date2', '開催期間');
                    $form->text('venue', __('会場'));
                });
        })->tab('セットリスト1', function ($form) {
            $form->table('setlist1', 'セットリスト1', function ($table) {
                $table->text('date', '日付');
                $table->select('id', '曲')->options(Song::all()->pluck('title', 'id'));
                $table->switch('is_daily', '')->states([
                    'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                    'off' => ['value' => 0, 'text' => '日替わり',   'color' => 'default'],
                ])->default(0);
                $table->switch('is_encore', '')->states([
                    'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                    'off' => ['value' => 0, 'text' => 'アンコール',   'color' => 'default'],
                ])->default(0);
                $table->text('exception', '例外');
            });
        })->tab('セットリスト2', function ($form) {
            $form->table('setlist2', 'セットリスト2', function ($table) {
                $table->text('date', '日付');
                $table->select('id', '曲')->options(Song::all()->pluck('title', 'id'));
                $table->switch('is_daily', '')->states([
                    'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                    'off' => ['value' => 0, 'text' => '日替わり',   'color' => 'default'],
                ])->default(0);
                $table->switch('is_encore', '')->states([
                    'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                    'off' => ['value' => 0, 'text' => 'アンコール',   'color' => 'default'],
                ])->default(0);
                $table->text('exception', '例外');
            });
        })->tab('セットリスト3', function ($form) {
            $form->table('setlist3', 'セットリスト3', function ($table) {
                $table->text('date', '日付');
                $table->select('id', '曲')->options(Song::all()->pluck('title', 'id'));
                $table->switch('is_daily', '')->states([
                    'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                    'off' => ['value' => 0, 'text' => '日替わり',   'color' => 'default'],
                ])->default(0);
                $table->switch('is_encore', '')->states([
                    'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                    'off' => ['value' => 0, 'text' => 'アンコール',   'color' => 'default'],
                ])->default(0);
                $table->text('exception', '例外');
            });
        })->tab('セットリスト4', function ($form) {
            $form->table('setlist4', 'セットリスト4', function ($table) {
                $table->text('date', '日付');
                $table->select('id', '曲')->options(Song::all()->pluck('title', 'id'));
                $table->switch('is_daily', '')->states([
                    'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                    'off' => ['value' => 0, 'text' => '日替わり',   'color' => 'default'],
                ])->default(0);
                $table->switch('is_encore', '')->states([
                    'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                    'off' => ['value' => 0, 'text' => 'アンコール',   'color' => 'default'],
                ])->default(0);
                $table->text('exception', '例外');
            });
        })->tab('セットリスト5', function ($form) {
            $form->table('setlist5', 'セットリスト5', function ($table) {
                $table->text('date', '日付');
                $table->select('id', '曲')->options(Song::all()->pluck('title', 'id'));
                $table->switch('is_daily', '')->states([
                    'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                    'off' => ['value' => 0, 'text' => '日替わり',   'color' => 'default'],
                ])->default(0);
                $table->switch('is_encore', '')->states([
                    'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                    'off' => ['value' => 0, 'text' => 'アンコール',   'color' => 'default'],
                ])->default(0);
                $table->text('exception', '例外');
            });
        })->tab('セットリスト6', function ($form) {
            $form->table('setlist6', 'セットリスト6', function ($table) {
                $table->text('date', '日付');
                $table->select('id', '曲')->options(Song::all()->pluck('title', 'id'));
                $table->switch('is_daily', '')->states([
                    'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                    'off' => ['value' => 0, 'text' => '日替わり',   'color' => 'default'],
                ])->default(0);
                $table->switch('is_encore', '')->states([
                    'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                    'off' => ['value' => 0, 'text' => 'アンコール',   'color' => 'default'],
                ])->default(0);
                $table->text('exception', '例外');
            });
        })->tab('コメント', function ($form) {
            $form->textarea('schedule', __('スケジュール'))->rows(15);
            $form->textarea('text', __('コメント'))->rows(15);
        });

        return $form;
    }
}
