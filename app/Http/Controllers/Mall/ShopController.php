<?php

namespace App\Http\Controllers\Mall;

use App\Http\Requests\ShopRequest;
use App\Models\Mall\Shop;
use App\Models\User;
use Gate;
use Request;
use App\Http\Controllers\Controller;

class ShopController extends Controller
{
    public function __construct()
    {
         $this->middleware('auth');
    }

    //显示页面
   	public function index()
   	{
   		if (Gate::denies('@mall')) {
            $this->middleware('deny403');
        }

        $shopType = Request::get('shop_type');

   		return view('shops.index', compact('shopType'));
   	}

    public function geocoder()
    {
        $address = Request::get('address');
        $req['address'] = $address;
        $req['output'] = 'json';
        $req['key'] = '6eea93095ae93db2c77be9ac910ff311';
        $req['city'] = '南宁市';
        $params = http_build_query($req);
        $response = file_get_contents('http://api.map.baidu.com/geocoder?'.$params);
        exit($response);
   	}

   	public function table($shopType)
    {
        return Shop::table($shopType);
    }

    public function create(){
        
        return view('shops.create');
    }

    /**
     * @param ShopRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(ShopRequest $request)
    {
        if (Request::isMethod('post')){

            $input = Request::all();
            if (!empty($input['type'])) {

                $input['type'] = implode(',', $input['type']);
            }
            $input['password'] = md5(substr($input['shopkeeper_mobile'], 5, 6));
            $input['status'] = 1;
            $insert = Shop::create($input);

            if ($insert){
                \Session::flash('flash_success', '添加成功');
            } else {
                \Session::flash('flash_warning', '添加失败');
            }
            return redirect('/mall/shop');
        }
    }

    public function edit($id, Request $request)
    {
        if (Request::isMethod('PATCH')){

            $shop = Shop::find($id);

            if ($shop == null) {
                \Session::flash('flash_warning', '无此记录');
                return redirect()->to($this->getRedirectUrl())
                    ->withInput($request->input());
            }

            $input = Request::all();
            $input['type'] = implode(',', $input['type']);
            if(empty($input['password'])){
                unset($input['password']);
            }else{
                $input['password'] = md5($input["password"]);
            }
            $input['status'] = 0;
            $update = $shop->update($input);

            if ($update){
                \Session::flash('flash_success', '修改成功');
            } else {
                \Session::flash('flash_warning', '修改失败');
            }
            return redirect('/mall/shop');

        } else {

            $shop = Shop::find($id);
            if ($shop == null) {
                \Session::flash('flash_warning', '无此记录');
                return redirect('/mall/ship');
            }
            $license_image = explode('|', $shop->license_image);
            $authentication_image = explode('|', $shop->authentication_image);
            $public_service = explode('|', $shop->public_service);
            $hygienic_license = explode('|', $shop->hygienic_license);
            $images = explode('|', $shop->images);
            $shopType = explode(',', $shop->type);

            return view('shops.edit', compact('shop', 'license_image', 'authentication_image', 'public_service', 'hygienic_license', 'shopType', 'images'));
        }

    }
   
    //商家管理删除页面
    public function delete($id){

        $shop = Shop::find($id);
        $shop->disabled = 1;
        $deleted = $shop->save();
        if ($deleted){
            \Session::flash('flash_success', '商家删除成功');
        } else {
            \Session::flash('flash_warning', '商家删除失败');
        }

    }

    public function push($shopId){

        $shopInfo = Shop::find($shopId);
        $positions = explode(',', $shopInfo->position);
        $shopType = explode(',', $shopInfo->type);
        return view('shops.push', compact('shopId', 'shopInfo', 'positions', 'shopType'));

    }

    public function shopPush($shopId)
    {

        $shop = Shop::find($shopId);

        $input = Request::all();
        if (!empty($input['position'])) {

            $input['position'] = implode(',', $input['position']);
        } else {
            $input['position'] = 0;
        }

        $update = $shop->update($input);

        if ($update){
            \Session::flash('flash_success', '推荐成功');
        } else {
            \Session::flash('flash_warning', '推荐失败');
        }
        return redirect('/mall/shop');
    }

    public function review(Request $request, $id)
    {
        if (Request::isMethod('PATCH')){

            $shop = Shop::find($id);

            if ($shop == null) {
                \Session::flash('flash_warning', '无此记录');
                return redirect()->to($this->getRedirectUrl())
                    ->withInput($request->input());
            }

            $input['status'] = Request::get('status');
            $auth = Request::get('authentication');
            $license = Request::get('license');
            if(!empty($auth)){$input['authentication'] = "1";}else{$input['authentication'] = "0";}
            if(!empty($license)){$input['license'] = "1";}else{$input['license'] = "0";}
            $input['reviewer'] = User::findOrFail(\Auth::user()->id)->name;
            $input['review_remark'] = Request::get('review_remark');
            $update = $shop->update($input);

            if ($update){
                \Session::flash('flash_success', '操作成功');
            } else {
                \Session::flash('flash_warning', '操作失败');
            }
            return redirect('/mall/shop');

        } else {

            $shop = Shop::find($id);
            if ($shop == null) {
                \Session::flash('flash_warning', '无此记录');
                return redirect('/mall/ship');
            }
            $license_image = explode('|', $shop->license_image);
            $authentication_image = explode('|', $shop->authentication_image);
            $public_service = explode('|', $shop->public_service);
            $hygienic_license = explode('|', $shop->hygienic_license);
            $shopType = explode(',', $shop->type);

            return view('shops.review', compact('shop', 'license_image', 'authentication_image', 'public_service', 'hygienic_license', 'shopType'));
        }
    }

    //处理排序
    public function sort()
    {
        return Shop::sort();
    }

}

