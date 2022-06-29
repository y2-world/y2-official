<?php

namespace App\Admin\Controllers;

use App\Models\Song;
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
        $grid->column('album_id', __('アルバム'))
        ->using(['1' => 'EVERYTHING',
        '2' => 'Kind of Love', 
        '3' => "Versus", 
        '4' => 'Atomic Heart', 
        '5' => '深海', 
        '6' => 'BOLERO', 
        '7' => 'DISCOVERY',
        '8' => '1/42',
        '9' => 'Q',
        '10' => 'Mr.Children 1992-1995',
        '11' => 'Mr.Children 1996-2000',
        '12' => "IT'S A WONDERFUL WORLD",
        '13' => 'シフクノオト',
        '14' => 'I ♡ U',
        '15' => 'HOME',
        '16' => 'B-SIDE',
        '17' => 'SUPERMARKET FANTASY',
        '18' => 'SENSE',
        '19' => 'Mr.Children 2001-2005 <micro>',
        '20' => 'Mr.Children 2005-2010 <macro>',
        '21' => '[(an imitation) blood orange]',
        '22' => 'REFLECTION',
        '23' => '重力と呼吸',
        '24' => 'SOUNDTRACKS',
        '25' => 'Mr.Children 2011-2015',
        '26' => 'Mr.Children 2015-2021 & NOW' ]);
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
        $show->field('album_id', __('アルバム'));
        $show->field('single_trk', __('#'));
        $show->field('single_id', __('シングル'));

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
        '10' => 'Mr.Children 1992-1995',
        '11' => 'Mr.Children 1996-2000',
        '12' => "IT'S A WONDERFUL WORLD",
        '13' => 'シフクノオト',
        '14' => 'I ♡ U',
        '15' => 'HOME',
        '16' => 'B-SIDE',
        '17' => 'SUPERMARKET FANTASY',
        '18' => 'SENSE',
        '19' => 'Mr.Children 2001-2005 <micro>',
        '20' => 'Mr.Children 2005-2010 <macro>',
        '21' => '[(an imitation) blood orange',
        '22' => 'REFLECTION',
        '23' => '重力と呼吸',
        '24' => 'SOUNDTRACKS',
        '25' => 'Mr.Children 2011-2015',
        '26' => 'Mr.Children 2015-2021 & NOW' ]);
        $form->text('album_trk', __('#'));
        $form->text('single_id', __('シングル'));
        $form->text('single_trk', __('#'));

        return $form;
    }
}
