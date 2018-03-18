<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Offer;

class PageController extends Controller
{
    public function index() {
        $CategoryControllerClass = new CategoriesController();
        $mainCategories = $CategoryControllerClass->getMainCategories();
        $offers = Offer::limit(12)->get()->toArray();  // ['id', 'text', 'images']
    	$arrOffers = $this->getOffers($offers);

        return view('main-page', [
            'mainCats' => $mainCategories,
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
}
