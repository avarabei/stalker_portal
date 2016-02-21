<?php

namespace Stalker\Lib\RESTAPI\v2;

class RESTApiResourceVideoGenres extends RESTApiCollection
{

    protected $params_map = array("video-categories" => "video.category");
    protected $categories;
    protected $prettyId = true;

    public function __construct(array $nested_params, array $external_params){
        parent::__construct($nested_params, $external_params);
        $this->document = new RESTApiVideoGenreDocument();

        if (!empty($this->nested_params['video.category'])){

            if (is_numeric($this->nested_params['video.category'])) {
                $this->prettyId = false;
            }

            $category = new \VideoCategory();
            $category_ids = explode(',', $this->nested_params['video.category']);

            foreach ($category_ids as $category_id) {
                $this->categories[] = $category->getById($category_id, $this->prettyId);
            }

            if (empty($this->categories)){
                throw new RESTNotFound("Category not found");
            }
        }
    }

    public function getCount(RESTApiRequest $request){
        $genres = new \VideoGenre();
        return count($genres->getAll($this->prettyId));
    }

    public function get(RESTApiRequest $request){

        $genres = new \VideoGenre();
        $genres->setLocale($request->getLanguage());

        if (!empty($this->categories)){

            if ($request->getParam('checkempty') == '1') {
                $video = new \Video();
                $videos = $video->getRawAll()->where(array('category_id' => $this->categories[0]['id']))->get()->all();
            }
            $response = array();

            if (count($this->categories) == 1){
                $response = $this->filter($genres->getByCategoryId($this->categories[0]['id'], $this->prettyId), $videos);
            }else{

                foreach ($this->categories as $category){

                    $response[] = array(
                        'id'     => $category['id'],
                        'genres' => $this->filter($genres->getByCategoryId($category['id'], $this->prettyId), $videos)
                    );
                }
            }

            return $response;
        }else{
            return $this->filter($genres->getAll($this->prettyId));
        }
    }

    private function filter($genres, $videos){

        $genres = array_map(function($genre){
            unset($genre['category_alias']);
            unset($genre['original_title']);
            unset($genre['_id']);
            return $genre;
        }, $genres);

        if (!empty($videos)) {

            $intersection = array();

            foreach ($genres as $key => $genre) {
                foreach ($videos as $video) {
                    $genreId = $genre['id'];
                    if ($genreId == $video['cat_genre_id_1'] ||
                        $genreId == $video['cat_genre_id_2'] ||
                        $genreId == $video['cat_genre_id_3'] ||
                        $genreId == $video['cat_genre_id_4']) {

                        $intersection[] = $genre;
                        unset($genre[$key]);
                        break;
                    }
                }
            }

            return $intersection;
        }

        return $genres;
    }
}
