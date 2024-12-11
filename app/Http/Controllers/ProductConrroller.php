<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\MajorCategory;
use Illuminate\Http\Request;

class ProductConrroller extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $keyword = $request->keyword;

        if ($request->category !== null) {
            // 商品と関連するレビューを一緒に取得
            $products = Product::with('reviews')->where('category_id', $request->category)->sortable()->paginate(10);
            $total_count = Product::where('category_id', $request->category)->count();
            $category = Category::find($request->category);
            $major_category = MajorCategory::find($category->major_category_id);
        } elseif ($keyword !== null) {
            // キーワード検索とレビューを一緒に取得
            $products = Product::with('reviews')->where('name', 'like', "%{$keyword}%")->sortable()->paginate(10);
            $total_count = $products->total();
            $category = null;
            $major_category = null;
        } else {
            // 全商品とレビューを一緒に取得
            $products = Product::with('reviews')->sortable()->paginate(10);
            $total_count = "";
            $category = null;
            $major_category = null;
        }

        // 商品ごとに平均評価を計算（四捨五入して0.5刻み）
        $products->each(function ($product) {
            $product->average_rating = round($product->reviews->avg('rating') * 2) / 2;  // 四捨五入して0.5刻み
        });

        $categories = Category::all();
        $major_categories = MajorCategory::all();

        return view('products.index', compact('products', 'category', 'major_category', 'categories', 'major_categories', 'total_count', 'keyword'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::all();

        return view('products.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $product = new Product();
        $product->name = $request->input('name');
        $product->description = $request->input('description');
        $product->price = $request->input('price');
        $product->category_id = $request->input('category_id');
        $product->save();

        return to_route('products.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        // 商品のレビューを取得
        $reviews = $product->reviews;

        // 平均評価を計算
        $averageRating = $reviews->avg('rating');  // レビューがあれば平均評価を取得
        $recently_product = Product::latest()->take(5)->get(); // 最近追加された5つの商品を取得

        // ビューに渡す
        return view('products.show', compact('product', 'reviews', 'averageRating', 'recently_product'));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $categories = Category::all();

        return view('products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {

        $product->name = $request->input('name');
        $product->description = $request->input('description');
        $product->price = $request->input('price');
        $product->category_id = $request->input('category_id');
        $product->update();

        return to_route('products.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return to_route('products.index');
    }
}
