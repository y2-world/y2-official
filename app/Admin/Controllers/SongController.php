<?php

namespace App\Admin\Controllers;

use App\Models\Song;
use App\Models\Album;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class SongController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Songs';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Song());

        $grid->column('id', __('ID'));
        $grid->column('song_id', __('song ID'));
        $grid->column('title', __('タイトル'));
        $grid->column('album_id', __('アルバム'))->display(function($id) {
            $album = optional(Album::find($id));
            if ($album) {
                return $album->title;
            }
        });
        $grid->column('album_trk', __('#'));
        $grid->column('single_id', __('シングル'))->display(function($id) {
            $single = optional(Album::find($id));
            if ($single) {
                return $single->title;
            }
        });
        $grid->column('single_trk', __('#'));

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
        $show = new Show(Song::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('song_id', __('song ID'));
        $show->field('title', __('タイトル'));
        $show->field('album_id', __('アルバム'))->as(function($id) {
            $album = optional(Album::find($id));
            if ($album) {
                return $album->title;
            }
        });
        $show->field('album_trk', __('#'));
        $show->field('single_id', __('シングル'))->as(function($id) {
            $single = optional(Single::find($id));
            if ($single) {
                return $single->title;
            }
        });
        $show->field('single_trk', __('#'));
        $show->field('text', __('コメント'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Song());

        $form->text('id', __('ID'))->rules('required');
        $form->text('song_id', __('song ID'));
        $form->text('title', __('タイトル'))->rules('required');
        $form->select('album_id', __('アルバム'))->options(function($id) {
            $album = Album::find($id);
            if ($album) {
                return [$album->id => $album->title];
            }
        })->ajax('admin/api/albums');
        $form->text('album_trk', __('#'));
        $form->text('album_disc', __('ディスク'));
        $form->select('single_id', __('シングル'))->options(function($id) {
            $single = Single::find($id);
            if ($single) {
                return [$single->id => $single->title];
            }
        })->ajax('admin/api/albums');
        $form->text('single_trk', __('#'));
        $form->textarea('text', __('コメント'));

        return $form;
    }
}
