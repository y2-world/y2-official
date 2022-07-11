<?php

namespace App\Admin\Controllers;

use App\Models\Festival;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FestivalController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Festivals';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Festival());

        $grid->column('id', __('ID'));
        $grid->column('title', __('タイトル'));
        $grid->column('date', __('公演日'))->default(date('Y.m.d'));
        $grid->column('venue', __('会場'));

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
        $show = new Show(Festival::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('title', __('タイトル'));
        $show->field('date', __('公演日'));
        $show->field('venue', __('会場'));
        $show->field('setlist', __('本編'))->unescape()->as(function ($setlist) {
            $result1 = [];
            foreach($setlist as $data1) {
                $result1[] = $data1['#'].'. '.$data1['song'].' / '.$data1['artist'];
            }
            return implode('<br>', $result1);
        });
        $show->field('encore', __('アンコール'))->unescape()->as(function ($encore) {
            $result2 = [];
            foreach((array)$encore as $data2) {
                $result2[] = $data2['#'].'. '.$data2['song'].' / '.$data2['artist'];
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
        $form = new Form(new Festival());

        $form->text('id', __('ID'))->rules('required');
        $form->text('title', __('タイトル'))->rules('required');
        $form->date('date', __('公演日'))->default(date('Y-m-d'))->rules('required');
        $form->text('venue', __('会場'))->rules('required');
        $form->table('setlist', __('本編'), function ($table) {
            $table->text('artist')->rules('required');
            $table->number('#')->rules('required');
            $table->text('song', __('楽曲'))->rules('required');
        });
        $form->table('encore', __('アンコール'), function ($table) {
            $table->text('artist')->rules('required');
            $table->number('#')->rules('required');
            $table->text('song', __('楽曲'))->rules('required');
        });


        return $form;
    }
}
