<?php

//Author: Tomas Navarro

namespace App\Http\Controllers;

use App\Product;
use Illuminate\Http\Request;
use App\Review;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;

//use App\User;

class ReviewController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) 
        {
            if (Auth::user())
            {
                return $next($request);
            }

            return redirect()->route('home.index');
        });
    }

    public function create(Product $product)
    {
        $data = []; //to be sent to the view
        $data["title"] = "Create a Review";
        $data['product'] = $product;
        
        return view('review.create')->with("data",$data);
    }

    public function save(Request $request, Product $product)
    {

        $request->validate(Review::validateRules());
        $user = User::findOrFail(Auth::user()->getId());

        $review = new Review;
        $review->user_id = $user->getId();
        $review->product_id = $product->getId();
        $review->title = $request["title"];
        $review->description = $request["description"];
        $review->score = $request["score"];

        $product_rating = $product->getRating();
        $num_ratings = count(Review::where('product_id',$product->getId())->get());
        $product_rating = (($product_rating * $num_ratings)+($request["score"]))/($num_ratings+1);
        Product::where('id',$product->getId())->update(['rating' => $product_rating]);
        
        $review->save();

        $message = Lang::get('messages.reviewSavedSuccess');

        return redirect()->route('product.show' ,$product->getId())->with("success",$message);
    }

    public function edit($id)
    {
        $data = [];
        
        $review = Review::findOrFail($id);

        $data["title"] = "Edit Review";

        $data['review'] = $review;

        return view('review.edit')->with(["data" => $data]);
    }

    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        $request->validate(Review::validateRules());

        $review->update($request->all());

        $product_rating = Product::findOrFail($review->product_id)->getRating();
        $num_rating = count(Review::where('product_id',$review->product_id)->get());
        $product_rating = (($product_rating * $num_rating)-($review->getScore()))/($num_rating-1);
        Product::where('id',$review->product_id)->update(['rating' => $product_rating]);

        $num_ratings = count(Review::where('product_id',$request->product_id)->get());
        $product_rating = (($product_rating * $num_ratings)+($request["score"]))/($num_ratings+1);
        Product::where('id',$request->product_id)->update(['rating' => $product_rating]);


        $message = Lang::get('messages.reviewEditSuccess');
        
        return redirect()->route('product.show', [$review->product_id])->with('success',$message);

    }

    public function delete($id)
    {
        $review = Review::find($id);
        
        $product_rating = Product::findOrFail($review->product_id)->getRating();
        $num_rating = count(Review::where('product_id',$review->product_id)->get());

        if ($num_rating - 1 == 0){
            $product_rating = 0;
        }else{
            $product_rating = (($product_rating * $num_rating)-($review->getScore()))/($num_rating-1);
        }
        
        $update = Product::where('id',$review->product_id)->update(['rating' => $product_rating]);

        $review->delete();

        $message = Lang::get('messages.reviewDeleteSuccess');

        return redirect()->route('product.show', [$review->product_id])->with('success',$message);
    }


}
