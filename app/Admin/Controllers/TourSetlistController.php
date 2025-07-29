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
    protected $title = 'Tour Setlists';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new TourSetlist());

        $grid->column('id', __('ID'));
        $grid->column('tour_id', __('ツアー'))->display(function ($id) {
            $tour = optional(Tour::find($id));
            return $tour ? $tour->title : '';
        });
        $grid->column('order_no', __('順番'));
        $grid->column('date1', __('日付1'))->display(function ($value) {
            return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : '';
        });

        $grid->column('date2', __('日付2'))->display(function ($value) {
            return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : '';
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
        $show->field('date1', __('日付1'))->as(function ($value) {
            return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : '';
        });
        $show->field('date2', __('日付2'))->as(function ($value) {
            return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : '';
        });
        $show->field('subtitle', __('サブタイトル'));
        $show->field('setlist', __('本編'))->unescape()->as(function ($setlist) {
            $result1 = [];
            $i = 1;
            foreach ((array)$setlist as $data1) {
                if (is_array($data1) && isset($data1['song'])) {
                    $song = \App\Models\Song::find($data1['song']);
                    $title = $song ? $song->title : $data1['song'];
                    $result1[] = $i . '. ' . $title;
                    $i++;
                }
            }
            return implode('<br>', $result1);
        });
        $show->field('encore', __('アンコール'))->unescape()->as(function ($encore) {
            $result2 = [];
            $i = 1;
            foreach ((array)$encore as $data2) {
                if (is_array($data2) && isset($data2['song'])) {
                    $song = \App\Models\Song::find($data2['song']);
                    $title = $song ? $song->title : $data2['song'];
                    $result2[] = $i . '. ' . $title;
                    $i++;
                }
            }
            return implode('<br>', $result2);
        });
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
        $form->saved(function (Form $form) {
            admin_toastr('保存しました！', 'success');
            return redirect(admin_url('tour-setlists'));
        });

        $form->select('tour_id', __('ツアー'))->options(Tour::all()->pluck('title', 'id'));
        $form->number('order_no', __('順番'))->default(1);
        $form->date('date1', __('日付1'))->default(null);
        $form->date('date2', __('日付2'))->default(null);
        $form->saving(function (Form $form) {
            if ($form->date1 === '') {
                $form->date1 = null;
            }
            if ($form->date2 === '') {
                $form->date2 = null;
            }
        });
        $form->text('subtitle', __('サブタイトル'));
        $form->table('setlist', '本編', function ($table) {
            $table->select('song', ' ')
                ->options(function ($value) {
                    $songs = \App\Models\Song::all()->pluck('title', 'id');

                    if (!is_numeric($value) && $value) {
                        return $songs->prepend($value, $value);
                    }

                    return $songs;
                })
                ->attribute([
                    'data-tags' => 'true',
                    'data-placeholder' => '曲を選択または入力',
                    'style' => 'width: 100%;',
                ]);

            $table->switch('is_daily', '<daily>')
                ->states([
                    'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                    'off' => ['value' => 0, 'text' => '',   'color' => 'default'],
                ])
                ->default(0);
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
            $table->switch('is_daily', '<daily>')->states([
                'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                'off' => ['value' => 0, 'text' => '',   'color' => 'default'],
            ])->default(0);
        });

        return $form;
    }
}
