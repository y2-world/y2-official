<?php

namespace App\Admin\Controllers;

use App\Models\Disco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DiscoController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Disco';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Disco());

        $grid->column('id', __('ID'));
        $grid->column('title', __('タイトル'));
        $grid->column('subtitle', __('サブタイトル'));
        $grid->column('date', __('リリース日'));
        $grid->column('created_at', __('作成日時'));
        $grid->column('updated_at', __('更新日時'));

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
        $show = new Show(Disco::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('title', __('タイトル'));
        $show->field('subtitle', __('サブタイトル'));
        $show->field('date', __('リリース日'));
        $show->field('tracklist', __('収録曲'))->unescape()->as(function ($tracklist) {
            $result = [];
            foreach($tracklist as $data) {
                $result[] = $data['#'].'. '.$data['title'];
            }
            return implode('<br>', $result);
        });
        $show->field('text', __('テキスト'));
        $show->field('image', __('画像'));
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
        $form = new Form(new Disco());

        $form->text('id', __('ID'));
        $form->text('title', __('タイトル'));
        $form->text('subtitle', __('サブタイトル'));
        $form->date('date', __('リリース日'));
        $form->radio('type','タイプ')
            ->options([
                0 =>'シングル',
                1 =>'アルバム',
            ]);
        $form->table('tracklist', __('収録曲'), function ($table) {
            $table->number('#');
            $table->text('title', __('タイトル'));
        });
        $form->textarea('text', __('テキスト'));
        $form->image('image', __('画像'))->move('images')->uniqueName();

        return $form;
    }
}
