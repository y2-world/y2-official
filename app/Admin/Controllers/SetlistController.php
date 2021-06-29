<?php

namespace App\Admin\Controllers;

use App\Setlist;
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
        $grid->column('artist', __('アーティスト'));
        $grid->column('tour_title', __('ツアータイトル'));
        $grid->column('date', __('公演日'))->default(date('Y.m.d'));
        $grid->column('venue', __('会場'));
        $grid->field('created_at', __('created_at'))->default(date('Y.m.d'));
        $grid->field('updated_at', __('updated_at'))->default(date('Y.m.d'));

        $grid->filter(function($filter){
            $filter->like('artist', 'アーティスト');
            $filter->like('tour_title', 'ツアータイトル');
            $filter->year('date', '年');
            $filter->like('venue', __('会場'));
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
        $show->field('artist', __('アーティスト'));
        $show->field('tour_title', __('ツアータイトル'));
        $show->field('date', __('公演日'));
        $show->field('venue', __('会場'));
        $show->field('setlist', __('セットリスト'))->unescape()->as(function ($setlist) {
            $result = [];
            foreach($setlist as $data) {
                $result[] = $data['#'].'.'.$data['楽曲'];
            }
            return implode('<br>', $result);
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

        $form->text('artist', __('アーティスト'))->rules('required');
        $form->text('tour_title', __('ツアータイトル'))->rules('required');
        $form->date('date', __('公演日'))->default(date('Y-m-d'))->rules('required');
        $form->text('venue', __('会場'))->rules('required');
        $form->table('setlist', __('セットリスト'), function ($table) {
            $table->number('#')->rules('required');
            $table->text('楽曲')->rules('required');
        });

        return $form;
    }

}
