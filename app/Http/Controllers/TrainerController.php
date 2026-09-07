<?php
namespace App\Http\Controllers;
use App\Models\Trainer;
use Illuminate\Http\Request;
use Inertia\Inertia;
class TrainerController extends Controller
{
 private function admin():void{abort_unless(auth()->check()&&auth()->user()->isAdmin(),403);}
 public function index(Request $request){$this->admin();$search=trim((string)$request->input('search',''));$items=Trainer::query()->when($search,fn($q)=>$q->where('name','like',"%{$search}%")->orWhere('specialty','like',"%{$search}%"))->latest()->paginate(10)->withQueryString();return Inertia::render('trainers/Index',['trainers'=>$items,'filters'=>['search'=>$search],'stats'=>['total'=>Trainer::count()]]);}
 public function show(Trainer $trainer){$this->admin();return Inertia::render('trainers/Show',['trainer'=>$trainer]);}
 public function store(Request $request){$this->admin();Trainer::create($request->validate(['name'=>'required|string|max:255','specialty'=>'nullable|string|max:255','phone'=>'nullable|string|max:255']));return back()->with('success','Trainer berhasil dibuat.');}
 public function update(Request $request,Trainer $trainer){$this->admin();$trainer->update($request->validate(['name'=>'required|string|max:255','specialty'=>'nullable|string|max:255','phone'=>'nullable|string|max:255']));return back()->with('success','Trainer berhasil diperbarui.');}
 public function destroy(Trainer $trainer){$this->admin();$trainer->delete();return back()->with('success','Trainer berhasil dihapus.');}
}
