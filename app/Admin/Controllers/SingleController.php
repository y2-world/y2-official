<?php

namespace App\Admin\Controllers;

use App\Models\Single;
use App\Models\Song;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class SingleController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Single';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Single());

        $grid->column('id', __('ID'));
        $grid->column('single_id', __('Single ID'));
        $grid->column('title', __('タイトル'));
        $grid->column('date', __('リリース日'));

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
        $show = new Show(Single::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('single_id', __('Single ID'));
        $show->field('title', __('タイトル'));
        $show->field('date', __('リリース日'));
        $show->field('download', __('配信シングル'));
        $show->field('trackilist', __('収録曲'))->unescape()->as(function ($trackilist) {
            $result1 = [];
            foreach($trackilist as $data1) {
                $result1[] = $data1['#'].'. '.$data1['song'];
            }
            return implode('<br>', $result1);
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
        $form = new Form(new Single());

        $form->text('id', __('ID'))->rules('required');
        $form->text('single_id', __('Single ID'));
        $form->text('title', __('タイトル'))->rules('required');
        $form->date('date', __('リリース日'));
        $form->switch('download', __('配信シングル'));
        $form->table('tracklist', __('収録曲'), function ($table) {
            $table->number('#')->rules('required');
            $table->select('song', __('楽曲'))->options(Song::all()->pluck('title', 'id'))->rules('required');
        });
        $form->textarea('text', __('コメント'));

        return $form;
    }
}
