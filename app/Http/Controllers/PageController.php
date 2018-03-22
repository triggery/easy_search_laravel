<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Offer;

class PageController extends Controller
{
    private $currLanguage;
    private $search;

    public function __construct(){
        $this->search = new SearchController();
        $this->currLanguage = "ru";
    }

    public function index() {
        $CategoryControllerClass = new CategoriesController();
        $mainCategories = $CategoryControllerClass->getMainCategories();
        $offers = Offer::limit(12)->get()->toArray();  // ['id', 'text', 'images']
    	$arrOffers = $this->getOffers($offers);

        $mainCats = [];
        foreach ($mainCategories as $key => $oneCat) {
            $word = $this->getCurrLangWord($oneCat);
            if(!empty($word))
                $mainCats[] = [ 'id' => $oneCat['id'], 'name' => $word ];
            else
                $mainCats[] = [ 'id' => $oneCat['id'], 'name' => $oneCat['name'] ];
        }

        //dd($mainCategories); // [ 'id' => , 'name' => , ]
        return view('main-page', [
            'mainCats' => $mainCats,
            'offers' => $arrOffers,
        ]);
    }

    public function viewOffer($id) {  
        $oneOffer = Offer::find($id)->toArray(); //['id', 'text', 'images']
        //$oneOffer = array_shift($oneOffer);
        $img = json_decode($oneOffer['images']);
        $OfferImages = str_replace('media', 'storage/images', $img);

        return view('viewOffer',  [
            'offer' => $oneOffer,
            'image' => array_shift($OfferImages),
        ]);
    }

    private function getCurrLangWord($wordsArr) {
        $words = [];
        $words = json_decode($wordsArr['words']);
        if(empty($words)) 
            return [];

        if($this->currLanguage == "ru")
            $words = $words->ru;
        else
            $words = $words->ro;
        return array_shift($words);
    }

    private function getOffers( $offers ) {
        $retOffers = [];

        foreach ($offers as $oneOffer) {
            $one = str_replace('media', 'storage/images', json_decode($oneOffer['images']));

            $retOffers[] = array('id' => $oneOffer['id'], 
                                'text' => $oneOffer['text'], 
                                'images' => array_shift($one), 
                                'price' => $oneOffer['price'],
                            );
        }
        return $retOffers;
    } 

    public function getOffersByCategoryID(Request $request) {
        
        $breadCrumb = [];
        $filters = [];
        $returnOffers = [];

        /*$res = $this->search->getNameCategoryWithId($request->categoryId);
        $string = $res['name'];*/

        $categoryId = [[ "id" => $request->categoryId ]];
        $final = $this->search->isFinalCategory($categoryId[0]);
        if($final == true) {
            $breadCrumb = $this->search->getBreadCrumb($categoryId);
            $returnOffers = $this->search->getOffersWithImagesByID($categoryId);
            //dd($returnOffers, $breadCrumb);
        }
        else {
            $breadCrumb = $this->search->getBreadCrumb($categoryId);
            $filters = $this->search->getChildsFilters($categoryId);
            $categoriesIds = $filters;
            $returnOffers = $this->search->getOffersWithImagesByID($categoriesIds);
            //dd($filters);
        }
        //dd("offers: ", $returnOffers, "breadCrumb: ",$breadCrumb, "filters: ",$filters);
        return view('search-page',  [
            //'searchString' => $string,
            'breadCrumb' => $breadCrumb,
            'filters' => $filters,
            'offers' => $returnOffers,
        ]);        
    }
}
