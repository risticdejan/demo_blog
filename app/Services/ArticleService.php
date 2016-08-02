<?php

namespace App\Services;

use App\Http\Requests\ArticleRequest;
use App\Models\Article;
use App\Repositories\Articles\ArticleRepositoryInterface;
use Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class ArticleService
{

    /**
     * @var ArticleRepositoryInterface
     */
    private $articleRepository;
    /**
     * @var UserActivityService
     */
    private $userActivity;

    /**
     * Create a new authentication controller instance.
     *
     * @param ArticleRepositoryInterface $articleRepository
     * @param UserActivityService $userActivity
     */
    public function __construct(ArticleRepositoryInterface $articleRepository, UserActivityService $userActivity)
    {
        $this->articleRepository = $articleRepository;

        $this->userActivity = $userActivity;
    }

    /**
     * Sync up the list of tags in the database.
     *
     * @param Article $article
     * @param array $tags
     * @internal param ArticleRequest $request
     */
    private function syncTags(Article $article, array $tags)
    {
        $article->tags()->sync($tags);
    }

    /**
     * @param $type
     * @param Article $article
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function ajaxHandler($type, Article $article, Request $request)
    {
        if (empty($article))
            return response()->json(404);

        if (Gate::denies($type, $article))
            return response()->json(403);

        if ($article->update([ $type => $request->input('value')]))
            return response()->json(200);

        return response()->json(500);
    }

    /**
     * Changes the value of the column in the database for the specified article.
     *
     * @param $column
     * @param Request $request
     * @param Article $article
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws AuthorizationException
     */
    public function changesValueInDb($column, Request $request, Article $article)
    {
        if ($request->ajax() || $request->wantsJson())
        {
            return $this->ajaxHandler($column, $article, $request);
        }

        if (Gate::denies($column, $article)) throw new AuthorizationException('This action is unauthorized.');

        $article->update([$column => $request->input('value')]);

        return redirect()->route('public.userCenters.articles',['user' => $article->user_id]);
    }

    /**
     * Save a new article.
     *
     * @param ArticleRequest $request
     * @return mixed
     */
    public function createArticle(ArticleRequest $request)
    {
        $tags = $this->ifItHasNewTagsCreate($request);

        $article = $this->articleRepository->createByUser('articles', $request->all());

        $this->userActivity->log($request, $article, 'Article "' . $article->title . '" was created');

        $this->syncTags($article, $tags);

        flash()->overlay('Article "'.$article->title.'" has been successfully created.', 'Article creating');

        return $article;
    }

    /**
     * Update an article.
     *
     * @param ArticleRequest $request
     * @param $article
     * @return mixed
     */
    public function updateArticle(ArticleRequest $request, $article)
    {
        $input = $request->all();

        $tags = $this->ifItHasNewTagsCreate($request);

        $input['comments'] = isset($input['comments']) ? $input['comments'] : 0;

        $article = $this->articleRepository->update($input, $article);

        $this->userActivity->log($request, $article, 'Article "' . $article->title . '" was updated');

        $this->syncTags($article, $tags);

        flash()->overlay('Article "'.$article->title.'" has been successfully updated.', 'Article updating');

        return $article;
    }

    /**
     * Delete an article
     *
     * @param ArticleRequest $request
     * @param $article
     */
    public function deleteArticle(ArticleRequest $request, $article)
    {
        $this->userActivity->log($request, $article, 'Article "' . $article->title . '" was deleted');

        flash()->overlay('Article "'.$article->title.'" has been successfully deleted.', 'Article deleting');

        $this->articleRepository->delete($article);
    }

    /**
     * @param Request $request
     * @return array|string
     */
    private function ifItHasNewTagsCreate(Request $request)
    {
        $tags = $request->input('tags');

        foreach ($tags as $ktag => $vtag)
        {
            if (starts_with($vtag, 'new:'))
            {
                $tag = $request->user()->tags()->create([
                    'name' => substr($vtag, 4),
                    'ip_address' => $request->ip()
                ]);

                $this->userActivity->log($request, $tag, 'Tag "' . $tag->name . '" was created');

                $tags[$ktag] = $tag->id;
            }
        }
        return $tags;
    }


}