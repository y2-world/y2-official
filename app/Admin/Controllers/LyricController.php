<?php

namespace App\Admin\Controllers;

use App\Models\Lyric;
use App\Models\Disco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class LyricController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Lyric';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Lyric());

        $grid->column('id', __('ID'));
        $grid->column('title', __('タイトル'));

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
        $show = new Show(Lyric::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('title', __('タイトル'));
        $show->field('album_id', __('アルバムID'));
        $show->field('single_id', __('シングルID'));
        $show->field('lyrics', __('歌詞'));
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
        $form = new Form(new Lyric());
        $form->saved(function (Form $form) {
            admin_toastr('保存しました！', 'success');
            return redirect(admin_url('lyrics'));
        });

        $form->text('id', __('ID'));
        $form->text('title', __('タイトル'));
        $form->select('album_id', __('アルバムID'))->options(Disco::all()->where('type', '=', "1")->pluck('title', 'id'));
        $form->select('single_id', __('シングルID'))->options(Disco::all()->where('type', '=', "0")->pluck('title', 'id'));
        $form->textarea('lyrics', __('歌詞'))->rows(70);


        return $form;
    }
}
