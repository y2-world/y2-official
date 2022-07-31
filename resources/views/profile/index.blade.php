@extends('layouts.app')
@section('content')
<div class="mt-4"></div>
<div class="profile">
    <div class="container">
        <div class="col-md-12">
            <h2>Profile</h2>
                <div class="element js-fadein"> 
                    <div class="row">
                        <div class="col-md-6 my-auto">
                            <div class="prof_img">
                                <img src={{ asset('images/profile_image.jpg') }} class="image" width="80%">
                            </div>
                        </div>
                        <div class="col-md-5 my-auto"> 
                            @if($profiles)
                                @foreach ($profiles as $profile)
                                    <br>
                                    <h2>{{$profile->name}}</h2>
                                    <p>{{$profile->info}}<p>
                                    <hr>
                                    <p class="profile-text">{!! nl2br(e($profile->text)) !!}</p>
                                    <hr>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection