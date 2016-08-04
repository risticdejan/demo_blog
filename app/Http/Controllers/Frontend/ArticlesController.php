<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleRequest;
use App\Models\Article;
use App\Repositories\Articles\ArticleRepositoryInterface;
use App\Services\ArticleService;
use App\User;

use App\Http\Requests;
use Illuminate\Http\Request;

class ArticlesController extends Controller
{

    /**
     * @var ArticleRepositoryInterface
     */
    private $articleRepository;

    /**
     * Create a new authentication controller instance.
     *
     * @param ArticleRepositoryInterface $articleRepository
     */
    public function __construct(ArticleRepositoryInterface $articleRepository)
    {
        $this->middleware('auth', ['except' => ['index', 'show', 'user']]);

        $this->articleRepository = $articleRepository;
    }

    /**
     * Changes the value of the status column in the database for the specified article.
     *
     * @param Article $article
     * @param Request $request
     * @param ArticleService $articleService
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function status(Article $article,Request $request, ArticleService $articleService)
    {
        return $articleService->changesValueInDb('status_active', $request, $article);
    }

    /**
     * Changes the value of the comments column in the database for the specified article.
     *
     * @param Article $article
     * @param Request $request
     * @param ArticleService $articleService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function comments(Article $article, Request $request, ArticleService $articleService)
    {
        return $articleService->changesValueInDb('status_comment', $request, $article);
    }

    /**
     * Display a listing of the article.
     *
     * @param ArticleRepositoryInterface $articleRepository
     * @return \Illuminate\Http\Response
     */
    public function index(ArticleRepositoryInterface $articleRepository)
    {
        $articles = $articleRepository->with(['user.profile'])->allPublishedArticles(4);

        return view('public.articles.index', compact('articles'));
    }

    /**
     * Display a listing of the article that written by a user.
     *
     * @param $name
     * @param ArticleRepositoryInterface $articleRepository
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function user($name, ArticleRepositoryInterface $articleRepository)
    {
        $user = User::where('name', $name)->first();

        $articles = $articleRepository->allPublishedArticlesForUser($user, 6);

        return view('public.articles.user', compact('articles','user'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('public.articles.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  ArticleRequest $request
     * @param ArticleService $articleService
     * @return \Illuminate\Http\Response
     */
    public function store(ArticleRequest $request, ArticleService $articleService)
    {
        $article = $articleService->createArticle($request);

        return redirect()->route('public.userCenters.articles',['user' => $article->user_id]);
    }

    /**
     * Display the specified resource.
     *
     * @param $slug
     * @param ArticleRepositoryInterface $articleRepository
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show($slug, ArticleRepositoryInterface $articleRepository)
    {
        $article = $articleRepository->findPublishedArticleWithSlug($slug);

        $tag_list_with_count = $articleRepository->getTagsWitCount($article);

        $article->increment('view_count');

        return view('public.articles.show', compact('article', 'tag_list_with_count'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Article $article
     * @return \Illuminate\Http\Response
     */
    public function edit(Article $article)
    {
        $this->authorize('edit', $article);

        return view('public.articles.edit', compact('article'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ArticleRequest $request
     * @param ArticleService $articleService
     * @param Article $article
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ArticleRequest $request, ArticleService $articleService, Article $article)
    {
        $this->authorize('update', $article);

        $articleService->updateArticle($request, $article);

        return redirect()->route('public.article.show',['article' => $article->slug]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ArticleRequest $request
     * @param ArticleService $articleService
     * @param  Article $article
     * @return \Illuminate\Http\Response
     */
    public function destroy(ArticleRequest $request, ArticleService $articleService, Article $article)
    {
        $this->authorize('delete', $article);

        $articleService->deleteArticle($request, $article);

        return redirect()->route('public.article.index');
    }



}
