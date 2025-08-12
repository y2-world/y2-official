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
        $grid->model()->orderBy('date', 'desc');

        $grid->column('id', __('ID'));
        $grid->column('artist_id', __('アーティスト'))->display(function ($id) {
            $artist = optional(Artist::find($id));
            return $artist ? $artist->name : '';
        });
        $grid->column('title', __('ツアータイトル'));
        $grid->column('venue', __('会場'));

        // 年リストを作成（例：2000年〜今年まで）
        $years = range(date('Y'), 2003);
        $years = array_combine($years, $years); // [2025 => 2025, 2024 => 2024, ...]

        $grid->filter(function ($filter) use ($years) {
            $filter->equal('artist_id', 'アーティスト')->select(
                Artist::where('visible', 0)->pluck('name', 'id')
            );
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
        if (!empty($show->getModel()->getAttribute('setlist'))) {
            $show->field('setlist', __('本編'))->unescape()->as(function ($setlist) {
                $result = [];
                $i = 1;
                foreach ((array)$setlist as $data) {
                    if (is_array($data) && isset($data['song'])) {
                        $result[] = $i . '. ' . $data['song'];
                        $i++;
                    }
                }
                return implode('<br>', $result);
            });
        }

        if (!empty($show->getModel()->getAttribute('encore'))) {
            $show->field('encore', __('アンコール'))->unescape()->as(function ($encore) {
                $result = [];
                $i = 1;
                foreach ((array)$encore as $data) {
                    if (is_array($data) && isset($data['song'])) {
                        $result[] = $i . '. ' . $data['song'];
                        $i++;
                    }
                }
                return implode('<br>', $result);
            });
        }

        if (!empty($show->getModel()->getAttribute('fes_setlist'))) {
            $show->field('fes_setlist', __('本編'))->unescape()->as(function ($setlist) {
                $result = [];
                $i = 1;
                foreach ((array)$setlist as $data) {
                    if (is_array($data) && isset($data['song'])) {
                        $result[] = $i . '. ' . $data['song'];
                        $i++;
                    }
                }
                return implode('<br>', $result);
            });
        }

        if (!empty($show->getModel()->getAttribute('fes_encore'))) {
            $show->field('fes_encore', __('アンコール'))->unescape()->as(function ($encore) {
                $result = [];
                $i = 1;
                foreach ((array)$encore as $data) {
                    if (is_array($data) && isset($data['song'])) {
                        $result[] = $i . '. ' . $data['song'];
                        $i++;
                    }
                }
                return implode('<br>', $result);
            });
        }
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

        $form->saved(function (Form $form) {
            admin_toastr('保存しました！', 'success');
            return redirect(admin_url('setlists'));
        });

        $form->text('id', __('ID'))->rules('required');

        $form->select('artist_id', __('アーティスト'))->options(Artist::all()->pluck('name', 'id'));

        $form->text('title', __('ツアータイトル'))->rules('required');

        $form->date('date', __('公演日'))->default(date('Y-m-d'))->rules('required');

        // 既存の会場候補取得
        $venueOptions = Setlist::query()
            ->select('venue')
            ->distinct()
            ->orderBy('venue', 'asc')
            ->pluck('venue')
            ->toArray();

        // datalistのoptionタグ生成
        $optionsHtml = '';
        foreach ($venueOptions as $venue) {
            $optionsHtml .= '<option value="' . e($venue) . '"></option>';
        }

        $form->text('venue', __('会場'))
            ->rules('required')
            ->attribute([
                'list' => 'venue_list',
                'autocomplete' => 'off',
            ])
            ->default($form->model()->venue ?? '');

        // datalistタグをhtmlで追加
        $form->html('<datalist id="venue_list">' . $optionsHtml . '</datalist>');


        $form->radio('fes', 'ライブ形態')
            ->options([
                0 => '単独ライブ',
                1 => 'フェス',
            ])->when(0, function (Form $form) {
                $form->table('setlist', __('本編'), function ($table) {
                    $table->text('song', '曲')->rules('required');
                    $table->switch('medley', 'Medley')->states([
                        'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                        'off' => ['value' => 0, 'text' => '',   'color' => 'default'],
                    ])->default(0);
                });
                $form->table('encore', __('アンコール'), function ($table) {
                    $table->text('song', __(''))->rules('required');
                    $table->switch('medley', 'Medley')->states([
                        'on'  => ['value' => 1, 'text' => '✔︎', 'color' => 'success'],
                        'off' => ['value' => 0, 'text' => '',   'color' => 'default'],
                    ])->default(0);
                });
            })->when(1, function (Form $form) {

                $form->table('fes_setlist', __('本編'), function ($table) {
                    $table->select('artist', '')
                        ->options(function ($value) {
                            $artists = \App\Artist::all()->pluck('name', 'id');

                            // 入力値が数値でない＝手入力（自由入力）だった場合
                            if (!is_numeric($value) && $value) {
                                return $artists->prepend($value, $value);
                            }

                            return $artists;
                        })
                        ->attribute([
                            'data-tags' => 'true',
                            'data-placeholder' => 'アーティストを選択または入力',
                        ]);
                    $table->text('song', __(''))->rules('required');
                });
                $form->table('fes_encore', __('アンコール'), function ($table) {
                    $table->select('artist', '')
                        ->options(function ($value) {
                            $artists = \App\Artist::all()->pluck('name', 'id');

                            if (!is_numeric($value) && $value) {
                                return $artists->prepend($value, $value);
                            }

                            return $artists;
                        })
                        ->attribute([
                            'data-tags' => 'true',
                            'data-placeholder' => 'アーティストを選択または入力',
                        ]);
                    $table->text('song', __(''))->rules('required');
                });
            });

        return $form;
    }
}
