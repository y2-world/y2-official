@extends('layouts.app')
@section('content')
    <div class="mt-4"></div>
    <div class="news">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <h2 class="news-title">News</h2>
                    <div class="element js-fadein">
                        <hr>
                        <div class="element js-fadein">
                            @foreach ($news as $new)
                                <a href="{{ route('news.show', $new->id) }}">
                                    <div class="date">{{ date('Y.m.d', strtotime($new->date)) }}</div>
                                    <h6>{{ $new->title }}</h6>
                                    <?php
                                    $text = strip_tags($new->text);
                                    ?>
                                    <p class="text">{{ Str::limit($text, 120, '…') }}</p>
                                    <hr>
                                </a>
                            @endforeach
                        </div>
                        <div class=”pagination”>
                            {!! $news->links() !!}
                        </div>
                        <br>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
