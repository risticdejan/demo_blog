<?php
namespace App\Repositories\Articles;

use App\Repositories\RepositoryInterface;

interface ArticleRepositoryInterface extends  RepositoryInterface
{
    /**
     * @param int $perPage
     * @param array $columns
     * @param string $pageName
     * @return mixed
     */
    public function allPublishedArticles($perPage = 8, $columns = array('*'), $pageName = 'page');

    /**
     * @param $user
     * @param int $perPage
     * @param array $columns
     * @param string $pageName
     * @return mixed
     */
    public function allPublishedArticlesForUser($user, $perPage = 8, $columns = array('*'), $pageName = 'page' );

    /**
     * @param $slug
     * @return mixed
     */
    public function findArticleWithSlug($slug);
}