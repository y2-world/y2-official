<?php

namespace App\Admin\Controllers;

use App\Models\TourSetlist;
use App\Models\Tour;
use App\Models\Song;
use App\Bio;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TourSetlistController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Tour Setlist';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TourSetlist());
        $grid->model()->orderBy('date1', 'desc');

        $grid->column('id', __('ID'));
        $grid->column('tour_id', __('ツアー'))->display(function ($id) {
            $tour = optional(Tour::find($id));
            return $tour ? $tour->title : '';
        });
        $grid->column('order_no', __('順番'));
        $grid->column('date1', __('日付1'))->display(function ($value) {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        });
        $grid->column('date2', __('日付2'))->display(function ($value) {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        });
        $grid->column('subtitle', __('サブタイトル'));

        // 年リストを作成（例：2000年〜今年まで）
        $years = range(date('Y'), 1992);
        $years = array_combine($years, $years); // [2025 => 2025, 2024 => 2024, ...]

        $grid->filter(function ($filter) use ($years) {
            // 年セレクトフィルター追加
            $filter->where(function ($query) {
                $query->whereYear('date', $this->input);
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
        $show = new Show(TourSetlist::findOrFail($id));
        $show->field('tour_id', __('ツアー'))->as(function ($id) {
            $tour = optional(Tour::find($id));
            return $tour ? $tour->title : '';
        });
        $show->field('order_no', __('順番'));
        $show->field('date1', __('日付1'));
        $show->field('date2', __('日付2'));
        $show->field('subtitle', __('サブタイトル'));
        $show->field('setlist', __('セットリスト'));
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
        $form = new Form(new TourSetlist());

        $form->select('tour_id', __('ツアー'))->options(Tour::all()->pluck('title', 'id'));
        $form->number('order_no', __('順番'))->default(1);
        $form->date('date1', __('日付1'))->default(date('Y-m-d'));
        $form->date('date2', __('日付2'))->default(date('Y-m-d'));
        $form->text('subtitle', __('サブタイトル'));
        $form->table('setlist', '本編', function ($table) {
            $table->select('song', '曲')
                ->options(function ($value) {
                    $songs = \App\Models\Song::all()->pluck('title', 'id');

                    // 入力値が数値でない＝手入力（自由入力）だった場合
                    if (!is_numeric($value) && $value) {
                        return $songs->prepend($value, $value);
                    }

                    return $songs;
                })
                ->attribute([
                    'data-tags' => 'true',
                    'data-placeholder' => '曲を選択または入力',
                ]);
            $table->switch('is_daily', '//daily//')->states([
                'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                'off' => ['value' => 0, 'text' => '',   'color' => 'default'],
            ])->default(0);
        });
        $form->table('encore', 'アンコール', function ($table) {
            $table->select('song', '曲')
                ->options(function ($value) {
                    $songs = \App\Models\Song::all()->pluck('title', 'id');

                    // 入力値が数値でない＝手入力（自由入力）だった場合
                    if (!is_numeric($value) && $value) {
                        return $songs->prepend($value, $value);
                    }

                    return $songs;
                })
                ->attribute([
                    'data-tags' => 'true',
                    'data-placeholder' => '曲を選択または入力',
                ]);
            $table->switch('is_daily', '//daily//')->states([
                'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                'off' => ['value' => 0, 'text' => '',   'color' => 'default'],
            ])->default(0);
        });

        return $form;
    }
}
