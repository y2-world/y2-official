<?php

namespace App\Admin\Controllers;

use App\Setlist;
use App\Artist;
use App\Year;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class SetlistController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'セットリスト';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Setlist());

        $grid->column('id', __('ID'));
        $grid->column('artist_id', __('アーティスト'))->display(function ($id) {
            $artist = optional(Artist::find($id));
            return $artist ? $artist->name : '';
        });
        $grid->column('title', __('ツアータイトル'));
        $grid->column('venue', __('会場'));

        // 年リストを作成（例：2000年〜今年まで）
        $years = range(date('Y'), 2000);
        $years = array_combine($years, $years); // [2025 => 2025, 2024 => 2024, ...]

        $grid->filter(function ($filter) use ($years) {
            $filter->like('artist', 'アーティスト');
            $filter->like('title', 'ツアータイトル');
            $filter->equal('fes', 'フェス')->radio([
                '' => 'All',
                0  => 'ライブ',
                1  => 'フェス',
            ]);
            $filter->like('venue', __('会場'));

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
        $show = new Show(Setlist::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('artist_id', __('アーティスト'))->as(function ($id) {
            $artist = optional(Artist::find($id));
            if ($artist) {
                return $artist->name;
            }
        });
        $show->field('title', __('ツアータイトル'));
        $show->field('date', __('公演日'));
        $show->field('venue', __('会場'));
        $show->field('setlist', __('本編'))->unescape()->as(function ($setlist) {
            $result1 = [];
            foreach ($setlist as $data1) {
                $result1[] = $data1['song'];
            }
            return implode('<br>', $result1);
        });
        $show->field('encore', __('アンコール'))->unescape()->as(function ($encore) {
            $result2 = [];
            foreach ((array)$encore as $data2) {
                $result2[] = $data2['song'];
            }
            return implode('<br>', $result2);
        });
        $show->field('fes_setlist', __('本編'))->unescape()->as(function ($fes_setlist) {
            $result3 = [];
            foreach ((array)$fes_setlist as $data3) {
                $result3[] = $data3['song'];
            }
            return implode('<br>', $result3);
        });
        $show->field('fes_encore', __('アンコール'))->unescape()->as(function ($fes_encore) {
            $result4 = [];
            foreach ((array)$fes_encore as $data4) {
                $result4[] = $data4['song'];
            }
            return implode('<br>', $result4);
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
        $form = new Form(new Setlist());

        $form->text('id', __('ID'))->rules('required');
        $form->select('artist_id', __('アーティスト'))->options(Artist::all()->pluck('name', 'id'));
        $form->text('title', __('ツアータイトル'))->rules('required');
        $form->date('date', __('公演日'))->default(date('Y-m-d'))->rules('required');
        $form->text('venue', __('会場'))->rules('required');
        $form->radio('fes', 'ライブ形態')
            ->options([
                0 => '単独ライブ',
                1 => 'フェス',
            ])->when(0, function (Form $form) {

                $form->table('setlist', __('本編'), function ($table) {
                    $table->text('song', __(''))->rules('required');
                    $table->switch('medley', '')->states([
                        'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                        'off' => ['value' => 0, 'text' => '',   'color' => 'default'],
                    ])->default(0);
                });
                $form->table('encore', __('アンコール'), function ($table) {
                    $table->text('song', __(''))->rules('required');
                    $table->switch('medley', '')->states([
                        'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                        'off' => ['value' => 0, 'text' => '',   'color' => 'default'],
                    ])->default(0);
                });
            })->when(1, function (Form $form) {

                $form->table('fes_setlist', __('本編'), function ($table) {
                    $table->select('artist', __(''))->options(Artist::all()->pluck('name', 'id'))->attribute(['class' => 'artist-select']);
                    $table->text('song', __(''))->rules('required');
                });
                $form->table('fes_encore', __('アンコール'), function ($table) {
                    $table->select('artist', __(''))->options(Artist::all()->pluck('name', 'id'))->attribute(['class' => 'artist-select']);
                    $table->text('song', __(''))->rules('required');
                });
            });

        return $form;
    }
}
