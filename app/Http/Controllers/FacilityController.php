<?php
namespace App\Http\Controllers;
use App\Models\Facility;
use Illuminate\Http\Request;
use Inertia\Inertia;
class FacilityController extends Controller
{
 private function admin():void{abort_unless(auth()->check()&&auth()->user()->isAdmin(),403);}
 public function index(Request $request){$this->admin();$search=trim((string)$request->input('search',''));$items=Facility::query()->when($search,fn($q)=>$q->where('name','like',"%{$search}%"))->latest()->paginate(10)->withQueryString();return Inertia::render('facilities/Index',['facilities'=>$items,'filters'=>['search'=>$search],'stats'=>['total'=>Facility::count()]]);}
 public function store(Request $request){$this->admin();Facility::create($request->validate(['name'=>'required|string|max:255','icon'=>'nullable|string|max:255']));return back()->with('success','Facility berhasil dibuat.');}
 public function update(Request $request,Facility $facility){$this->admin();$facility->update($request->validate(['name'=>'required|string|max:255','icon'=>'nullable|string|max:255']));return back()->with('success','Facility berhasil diperbarui.');}
 public function destroy(Facility $facility){$this->admin();$facility->delete();return back()->with('success','Facility berhasil dihapus.');}
}
