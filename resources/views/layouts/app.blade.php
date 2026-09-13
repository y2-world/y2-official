<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Yuki Official')</title>

    @php
        $ogTitle = trim($__env->yieldContent('og_title')) ?: (trim($__env->yieldContent('title')) ?: 'Yuki Official');
        $ogDescription = trim($__env->yieldContent('og_description')) ?: 'Yuki Yoshida Official Website - News, Tours, Discography, and Setlists';
        $ogImage = trim($__env->yieldContent('og_image')) ?: asset('/images/top_image.jpg');
        $ogType = trim($__env->yieldContent('og_type')) ?: 'website';
    @endphp

    <!-- OGP Meta Tags -->
    <meta property="og:title" content="{!! str_replace('&#039;', "'", $ogTitle) !!}">
    <meta property="og:description" content="{!! str_replace('&#039;', "'", $ogDescription) !!}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:site_name" content="Yuki Official">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Old+Mincho&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/e47a10189c.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="{{ asset('/css/main.css?time=' . time()) }}">

    @livewireStyles
</head>
<body>
<div class="container">
    <div class="nav">
        <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
            <a class="navbar-brand" href="{{ url('/') }}">Yuki Official
                <span class="logo">Yuki Yoshida Official Website</span></a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <div class="navbar-toggler-icon"></div>
            </button>
            <div class="sns-nav">
                {{-- <a href="https://www.facebook.com/yuki92496?locale=ja_JP" target="_blank"><i class="fab fa-facebook"></i></a>
            <a href="https://twitter.com/y2_engineer" target="_blank"> <i class="fab fa-twitter"></i></a>
            <a href="https://www.instagram.com/y2_world/" target="_blank"><i class="fab fa-instagram"> </i></a>
            <a href="https://github.com/y2-world" target="_blank"> <i class="fab fa-github"> </i></a> --}}
                <a href="https://music.apple.com/jp/artist/yuki-yoshida/1448865361?itsct=music_box_badge&itscg=30200&ct=artists_yuki_yoshida&app=music&ls=1"
                    target="_blank"><i class="fab fa-apple fa-xl"></i></a>
                <a href="https://open.spotify.com/artist/5x6TjqB9xXXjY4Xn5y2oJm" target="_blank"><i
                        class="fab fa-spotify fa-xl"> </i></a>
                <a href="https://www.youtube.com/user/yuki92496" target="_blank"><i
                        class="fab fa-youtube fa-xl"></i></a>
                <a href="https://open.spotify.com/show/5uQQnvpk9DSuY4rBwptQkZ" target="_blank"><i
                        class="fas fa-podcast fa-xl"></i></a>
                {{-- <a href="https://podcasts.apple.com/us/podcast/y2-radio/id1555086566?uo=4" target="_blank"><i class="fas fa-podcast"></i></a> --}}
            </div>
            <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
                <ul class="navbar-nav">
                    {{-- <li class="nav-item active">
                    <a class="nav-link" href="{{ url('/') }}">Home</a>
                </li> --}}
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/#news') }}">News</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/#music') }}">Music</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/#profile') }}">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/#radio') }}">Radio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/setlists') }}">Setlists</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/database') }}">Database</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/stats') }}">Stats</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('https://ameblo.jp/y2-world') }}"
                            target="_blank">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/admin') }}" target="_blank">Admin</a>
                    </li>
                    {{-- <li class="nav-item">
                        <a class="nav-link" href="{{ url('/filament') }}" target="_blank">Admin</a>
                    </li> --}}
                    <li class="nav-item">
                        @auth('external')
                            <a class="btn btn-outline-dark mypage-nav-button" href="{{ route('mypage.index') }}">My Page</a>
                        @else
                            <a class="btn btn-outline-dark mypage-nav-button" href="{{ route('mypage.login') }}">Login</a>
                        @endauth
                    </li>
                    <li class="nav-item">
                        <div class="mb-sns-nav">
                            <a href="https://music.apple.com/jp/artist/yuki-yoshida/1448865361?itsct=music_box_badge&itscg=30200&ct=artists_yuki_yoshida&app=music&ls=1"
                                target="_blank"><i class="fab fa-apple fa-2x"></i></a>
                            <a href="https://open.spotify.com/artist/5x6TjqB9xXXjY4Xn5y2oJm" target="_blank"><i
                                    class="fab fa-spotify fa-2x"> </i></a>
                            <a href="https://www.youtube.com/user/yuki92496" target="_blank"><i
                                    class="fab fa-youtube fa-2x"></i></a>
                            <a href="https://open.spotify.com/show/5uQQnvpk9DSuY4rBwptQkZ" target="_blank"><i
                                    class="fas fa-podcast fa-2x"></i></a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
            integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous">
        </script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"
            integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous">
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
        </script>
    </div>
</div>
<div class="header_space"></div>
@yield('content')

<script>
// どのページからでも呼べるトースト表示関数。Ajax操作の完了後などに使う。
function showAppToast(message) {
    var toast = document.createElement('div');
    toast.className = 'app-toast';
    toast.innerHTML = '<i class="fa-solid fa-circle-check"></i><span></span>';
    toast.querySelector('span').textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(function () {
        toast.classList.add('show');
    });
    setTimeout(function () {
        toast.classList.remove('show');
        setTimeout(function () { toast.remove(); }, 300);
    }, 3000);
}

@if (session('success'))
    document.addEventListener('DOMContentLoaded', function () {
        showAppToast(@json(session('success')));
    });
@endif

// Timelineの星評価編集・コメント投稿/削除。timeline/index.blade.phpとattendances/show.blade.phpで
// 同じ「.timeline-card」構造を共有し、この初期化関数から呼び出す。
// 一覧画面ではカード自体が個別ページへのリンク（<a>）になっているため、
// 星評価やユーザー名などカード内のクリック可能要素は stopPropagation でリンク遷移を防ぐ。
// コメント投稿フォームは個別ページ（attendances/show.blade.php）にしか無いため、
// 要素が無ければその部分の初期化はスキップする。
function initTimelineCard(card) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const starsDisplay = card.querySelector('.timeline-stars-display');
    const ratingEl = card.querySelector('.timeline-rating');

    // カード自体がリンク（<a>）の一覧画面で、ユーザー名・アーティスト名だけ別ページに
    // 飛ばしたい箇所（data-nav-url）。バブリングでカード本体のリンクへ遷移してしまわないよう
    // ここでpreventDefault/stopPropagationしてから明示的に遷移する。
    card.querySelectorAll('[data-nav-url]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            window.location.href = el.dataset.navUrl;
        });
    });

    function renderStars(container, rating) {
        container.querySelectorAll('i').forEach(function (star, index) {
            star.classList.toggle('is-filled', index < rating);
        });
    }

    // 星評価（投稿者本人のみ編集可）：is-editableな星をクリックすると即座にその評価で保存する
    if (starsDisplay.classList.contains('is-editable')) {
        const updateUrl = ratingEl.dataset.updateUrl;
        const stars = starsDisplay.querySelectorAll('i');

        stars.forEach(function (star, index) {
            star.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const newRating = index + 1;
                renderStars(starsDisplay, newRating);
                starsDisplay.dataset.rating = newRating;

                fetch(updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ rating: newRating }),
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        showAppToast(data.message);
                    });
            });
        });
    }

    const commentForm = card.querySelector('.timeline-comment-form');
    const commentFormInput = card.querySelector('.timeline-comment-form-input');
    const commentsContainer = card.querySelector('.timeline-comments');
    const commentCount = card.querySelector('.timeline-card-comment-count');
    let noCommentsMessage = card.querySelector('.timeline-no-comments');

    function updateCommentCount(delta) {
        if (!commentCount) return;
        const current = parseInt(commentCount.textContent.trim(), 10) || 0;
        commentCount.innerHTML = '<i class="fa-regular fa-comment"></i> ' + (current + delta);
    }

    function setupCommentDelete(btn) {
        if (!btn.dataset.deleteUrl) return;
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (!confirm('このコメントを削除しますか？')) return;
            fetch(btn.dataset.deleteUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    btn.closest('.timeline-comment-item').remove();
                    updateCommentCount(-1);
                    showAppToast(data.message);
                });
        });
    }

    function setupCommentEdit(btn) {
        if (!btn.dataset.updateUrl) return;
        const item = btn.closest('.timeline-comment-item');
        const bodyDisplay = item.querySelector('.timeline-comment-body');
        const bodyInput = item.querySelector('.timeline-comment-body-input');
        const penIcon = btn.querySelector('.fa-pen');
        const checkIcon = btn.querySelector('.fa-check');
        let isEditing = false;

        const resizeBodyInput = function () {
            bodyInput.style.height = 'auto';
            bodyInput.style.height = bodyInput.scrollHeight + 'px';
        };

        const startEdit = function () {
            isEditing = true;
            bodyDisplay.hidden = true;
            bodyInput.hidden = false;
            resizeBodyInput();
            bodyInput.focus();
            bodyInput.setSelectionRange(bodyInput.value.length, bodyInput.value.length);
            penIcon.hidden = true;
            checkIcon.hidden = false;
        };

        const commitEdit = function () {
            const newBody = bodyInput.value.trim();
            if (!newBody) return;

            fetch(btn.dataset.updateUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ body: newBody }),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    isEditing = false;
                    bodyDisplay.textContent = data.body;
                    bodyInput.value = data.body;
                    bodyInput.hidden = true;
                    bodyDisplay.hidden = false;
                    penIcon.hidden = false;
                    checkIcon.hidden = true;
                    showAppToast(data.message);
                });
        };

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (isEditing) {
                commitEdit();
            } else {
                startEdit();
            }
        });
        bodyInput.addEventListener('click', function (e) { e.stopPropagation(); });
        bodyInput.addEventListener('input', resizeBodyInput);
        bodyInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); commitEdit(); }
        });
    }

    if (commentForm) {
        commentForm.addEventListener('click', function (e) { e.stopPropagation(); });

        function autoResizeCommentInput() {
            commentFormInput.style.height = 'auto';
            commentFormInput.style.height = commentFormInput.scrollHeight + 'px';
        }
        commentFormInput.addEventListener('input', autoResizeCommentInput);

        commentForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const body = commentFormInput.value.trim();
            if (!body) return;

            fetch(commentForm.dataset.postUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ body: body }),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (noCommentsMessage) {
                        noCommentsMessage.remove();
                        noCommentsMessage = null;
                    }
                    const item = document.createElement('div');
                    item.className = 'timeline-comment-item';
                    item.dataset.commentId = data.comment.id;
                    item.innerHTML = '<span class="timeline-comment-user"></span>'
                        + '<span class="timeline-comment-body"></span>'
                        + '<textarea class="form-control timeline-comment-body-input" maxlength="1000" rows="1" hidden></textarea>'
                        + '<button type="button" class="timeline-comment-edit" data-update-url="' + data.comment.update_url + '" title="編集"><i class="fa-solid fa-pen"></i><i class="fa-solid fa-check" hidden></i></button>'
                        + '<button type="button" class="timeline-comment-delete" data-delete-url="' + data.comment.delete_url + '" title="削除"><i class="fa-solid fa-trash"></i></button>';
                    item.querySelector('.timeline-comment-user').textContent = data.comment.user_name;
                    item.querySelector('.timeline-comment-body').textContent = data.comment.body;
                    item.querySelector('.timeline-comment-body-input').value = data.comment.body;
                    commentsContainer.appendChild(item);
                    setupCommentEdit(item.querySelector('.timeline-comment-edit'));
                    setupCommentDelete(item.querySelector('.timeline-comment-delete'));
                    commentFormInput.value = '';
                    autoResizeCommentInput();
                    updateCommentCount(1);
                    showAppToast(data.message);
                });
        });
    }

    card.querySelectorAll('.timeline-comment-edit').forEach(setupCommentEdit);
    card.querySelectorAll('.timeline-comment-delete').forEach(setupCommentDelete);
}
</script>

<!-- JS -->
<script src='https://code.jquery.com/jquery-3.6.4.min.js'></script>
<script src="{{ asset('/js/main.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

<!-- ページごとのスクリプト -->
@yield('page-script')

<!-- Typeahead.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.11.1/typeahead.bundle.min.js"></script>

<!-- Bootstrap scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"
    integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous">
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
</script>
@livewireScripts
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
{{-- <footer id='footer'>
    <footer class="text-left bg-dark text-white">
        <div class="footer-main">
            <div class="container">
                <br>
                <div class="sns">
                    <a href="https://www.facebook.com/yuki92496?locale=ja_JP" target="_blank"><i class="fab fa-facebook fa-lg"></i></a>
                    <a href="https://twitter.com/y2_engineer" target="_blank"> <i class="fab fa-twitter fa-lg"></i></a>
                    <a href="https://www.instagram.com/y2_world/" target="_blank"><i class="fab fa-instagram fa-lg"> </i></a>
                    <a href="https://github.com/y2-world" target="_blank"> <i class="fab fa-github fa-lg"> </i></a>
                    <a href="https://music.apple.com/jp/artist/yuki-yoshida/1448865361?itsct=music_box_badge&itscg=30200&ct=artists_yuki_yoshida&app=music&ls=1" target="_blank"><i class="fab fa-apple fa-lg"></i></a>
                    <a href="https://open.spotify.com/artist/5x6TjqB9xXXjY4Xn5y2oJm" target="_blank"><i class="fab fa-spotify fa-lg"> </i></a>
                    <a href="https://www.youtube.com/user/yuki92496" target="_blank"><i class="fab fa-youtube fa-lg"></i></a>
                    <a href="https://podcasts.apple.com/us/podcast/y2-radio/id1555086566?uo=4" target="_blank"><i class="fas fa-podcast fa-lg"></i></a>
                </div>
                <br>
                <div class="hour">Yuki Yoshida Official Website</div>
            </div> 
        </div>
    </footer>  
</footer> --}}
