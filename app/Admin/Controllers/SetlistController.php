<?php

namespace App\Admin\Controllers;

use App\Setlist;
use App\Artist;
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
        $grid->column('artist_id', __('アーティスト'))->display(function($id) {
            return Artist::find($id)->name;
        });
        $grid->column('tour_title', __('ツアータイトル'));
        $grid->column('date', __('公演日'))->default(date('Y.m.d'));
        $grid->column('venue', __('会場'));
        $grid->column('created_at', __('作成日'));
        $grid->column('updated_at', __('更新日'))->default(date('Y.m.d'));

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
        $show->field('artist_id', __('アーティストID'))->using(['1' => 'w-inds.',
        '2' => 'Mr.Children', 
        '3' => "B'z", 
        '4' => 'flumpool', 
        '5' => '福山雅治', 
        '6' => 'コブクロ', 
        '7' => '小池美由',
        '8' => 'SE7EN',
        '9' => 'Tak Matsumoto & Daniel Ho',
        '10' => 'スキマスイッチ',
        '11' => 'Kis-My-Ft2',
        '12' => 'CHEMISTRY',
        '13' => 'Charlie Puth',
        '14' => 'ウカスカジー',
        '15' => '嵐',
        '16' => 'フラチナリズム',
        '17' => 'Official髭男dism',
        '18' => 'Nissy']);
        $show->field('tour_title', __('ツアータイトル'));
        $show->field('date', __('公演日'));
        $show->field('venue', __('会場'));
        $show->field('setlist', __('本編'))->unescape()->as(function ($setlist) {
            $result1 = [];
            foreach($setlist as $data1) {
                $result1[] = $data1['#'].'. '.$data1['song'];
            }
            return implode('<br>', $result1);
        });
        $show->field('encore', __('アンコール'))->unescape()->as(function ($encore) {
            $result2 = [];
            foreach((array)$encore as $data2) {
                $result2[] = $data2['#'].'. '.$data2['song'];
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
        $form = new Form(new Setlist());

        $form->text('id', __('ID'))->rules('required');
        $form->select('artist_id', __('アーティスト'))->options(function($id) {
            $artist = Artist::find($id);
            if ($artist) {
                return [$artist->id => $artist->name];
            }
        })->ajax('admin/api/artists');
        $form->text('tour_title', __('ツアータイトル'))->rules('required');
        $form->date('date', __('公演日'))->default(date('Y-m-d'))->rules('required');
        $form->text('venue', __('会場'))->rules('required');
        $form->table('setlist', __('本編'), function ($table) {
            $table->number('#')->rules('required');
            $table->text('song', __('楽曲'))->rules('required');
        });
        $form->table('encore', __('アンコール'), function ($table) {
            $table->number('#')->rules('required');
            $table->text('song', __('楽曲'))->rules('required');
        });

        return $form;
    }

}
