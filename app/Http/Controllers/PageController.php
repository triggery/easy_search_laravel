<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Offer;

class PageController extends Controller
{
    private $availableLangugages;
    private $currLanguage;
    private $search;

    public function __construct(){
        $this->search = new SearchController();
        $this->currLanguage = Redis::get('currLanguage');   //"ru";
        //Redis::set('currLanguage', $this->currLanguage);
    }

    public function index() {

        //$lang = Redis::get('currLanguage');
        //dd($lang);

        $CategoryControllerClass = new CategoriesController();
        $mainCategories = $CategoryControllerClass->getMainCategories();
        $offers = Offer::limit(12)->get()->toArray();  // ['id', 'text', 'images']
    	$arrOffers = $this->replaceImagePathInOffers($offers);

        $mainCats = [];
        foreach ($mainCategories as $oneCat) {
            $word = $this->getCurrLangWord($oneCat);
            $mainCats[] = [ 'id' => $oneCat['id'], 'name' => empty($word) ? $oneCat['name'] : $word ];
        }

        //dd($mainCategories); // [ 'id' => , 'name' => , ]
        return view('main-page', [
            'mainCats' => $mainCats,
            'offers' => $arrOffers,
            'lang' => $this->currLanguage,
        ]);
    }

    public function setLang(Request $request, $lang) {

        Redis::set('currLanguage', $lang);
        $this->currLanguage = $lang;

        /*$path  = \Request::route()->getName();
        dd($path);*/

        return redirect()->route('root');
    }

    public function viewOffer($id) {  
        $oneOffer = Offer::find($id)->toArray(); //['id', 'text', 'images']
        //$oneOffer = array_shift($oneOffer);
        $img = json_decode($oneOffer['images']);
        $OfferImages = str_replace('media', 'storage/images', $img);

        return view('viewOffer',  [
            'offer' => $oneOffer,
            'image' => array_shift($OfferImages),
            'lang' => $this->currLanguage,
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

    private function replaceImagePathInOffers( $offers ) {
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
            'lang' => $this->currLanguage,
        ]);        
    }
}
