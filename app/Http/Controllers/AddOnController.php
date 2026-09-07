<?php
namespace App\Http\Controllers;
use App\Models\AddOn;
use Illuminate\Http\Request;
use Inertia\Inertia;
class AddOnController extends Controller
{
 private function admin():void{abort_unless(auth()->check()&&auth()->user()->isAdmin(),403);}
 public function index(Request $request){$this->admin();$search=trim((string)$request->input('search',''));$items=AddOn::query()->when($search,fn($q)=>$q->where('name','like',"%{$search}%"))->latest()->paginate(10)->withQueryString();return Inertia::render('add-ons/Index',['addOns'=>$items,'filters'=>['search'=>$search],'stats'=>['total'=>AddOn::count(),'stock'=>AddOn::sum('stock')]]);}
 public function show(AddOn $addOn){$this->admin();return Inertia::render('add-ons/Show',['addOn'=>$addOn]);}
 public function store(Request $request){$this->admin();AddOn::create($request->validate(['name'=>'required|string|max:255','price'=>'required|numeric|min:0','stock'=>'required|integer|min:0']));return back()->with('success','Add-on berhasil dibuat.');}
 public function update(Request $request,AddOn $addOn){$this->admin();$addOn->update($request->validate(['name'=>'required|string|max:255','price'=>'required|numeric|min:0','stock'=>'required|integer|min:0']));return back()->with('success','Add-on berhasil diperbarui.');}
 public function destroy(AddOn $addOn){$this->admin();$addOn->delete();return back()->with('success','Add-on berhasil dihapus.');}
}
