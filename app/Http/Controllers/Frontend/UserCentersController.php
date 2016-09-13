<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Repositories\Articles\ArticleRepositoryInterface;
use App\User;
use App\Http\Requests;
use Auth;

class UserCentersController extends Controller
{
    public function __construct()
    {
        view()->share('currentUser', Auth::user());
    }


    public function show(User $user)
    {
        $this->authorize('self',$user);

        $activities = $user->activities()->orderBy('created_at','dsc')->paginate(6);

        return view('public.userCenters.show',compact('activities','user'));
    }

    public function articles(User $user, ArticleRepositoryInterface $articleRepository)
    {
        $this->authorize('self',$user);

        $articles = $articleRepository->allArticlesForUser($user,2);

        return view('public.userCenters.articles',compact('articles','user'));
    }

    public function images(User $user)
    {
        $this->authorize('self',$user);

        return view('public.userCenters.images',compact('user'));
    }

    public function files(User $user)
    {
        $this->authorize('self',$user);

        return view('public.userCenters.files',compact('user'));
    }

    public function authorRequest()
    {
        $user = auth()->user();

        $user->update([
            'author_request' => 1
        ]);
        
        return redirect()->route('public.userCenters.articles',['user' => $user->id]);
    }
}
