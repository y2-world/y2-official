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
        $grid->column('album_trk', __('#'));
        $grid->column('album_id', __('アルバム'))->display(function($id) {
            $album = Album::find($id)->title;
            if(isset($album)) {
                return $album;
            } else {
                $album = '-';
                return $album;
            }
        });
        $grid->column('single_trk', __('#'));
        $grid->column('single_id', __('シングル'));

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
        $show->field('album_trk', __('#'));
        $show->field('album_id', __('アルバム'))
        ->using(['1' => 'EVERYTHING',
        '2' => 'Kind of Love', 
        '3' => "Versus", 
        '4' => 'Atomic Heart', 
        '5' => '深海', 
        '6' => 'BOLERO', 
        '7' => 'DISCOVERY',
        '8' => '1/42',
        '9' => 'Q',
        '10' => "IT'S A WONDERFUL WORLD",
        '11' => 'シフクノオト',
        '12' => 'I ♡ U',
        '13' => 'HOME',
        '14' => 'B-SIDE',
        '15' => 'SUPERMARKET FANTASY',
        '16' => 'SENSE',
        '17' => '[(an imitation) blood orange]',
        '18' => 'REFLECTION',
        '19' => '重力と呼吸',
        '20' => 'SOUNDTRACKS']);
        $show->field('single_trk', __('#'));
        $show->field('single_id', __('シングル'));
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
        $form->select('album_id', __('アルバム'))
        ->options(['1' => 'EVERYTHING',
        '2' => 'Kind of Love', 
        '3' => "Versus", 
        '4' => 'Atomic Heart', 
        '5' => '深海', 
        '6' => 'BOLERO', 
        '7' => 'DISCOVERY',
        '8' => '1/42',
        '9' => 'Q',
        '10' => "IT'S A WONDERFUL WORLD",
        '11' => 'シフクノオト',
        '12' => 'I ♡ U',
        '13' => 'HOME',
        '14' => 'B-SIDE',
        '15' => 'SUPERMARKET FANTASY',
        '16' => 'SENSE',
        '17' => '[(an imitation) blood orange',
        '18' => 'REFLECTION',
        '19' => '重力と呼吸',
        '20' => 'SOUNDTRACKS']);
        $form->text('album_trk', __('#'));
        $form->text('single_id', __('シングル'));
        $form->text('single_trk', __('#'));
        $form->textarea('text', __('コメント'));

        return $form;
    }
}
