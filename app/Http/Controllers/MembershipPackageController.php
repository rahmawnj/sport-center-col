<?php
namespace App\Http\Controllers;
use App\Models\MembershipPackage;
use Illuminate\Http\Request;
use Inertia\Inertia;
class MembershipPackageController extends Controller
{
 private function admin(): void { abort_unless(auth()->check() && auth()->user()->isAdmin(),403); }
 public function index(Request $request) { $this->admin(); $search=trim((string)$request->input('search','')); $items=MembershipPackage::query()->when($search,fn($q)=>$q->where('name','like',"%{$search}%"))->latest()->paginate(10)->withQueryString(); return Inertia::render('membership-packages/Index',['packages'=>$items,'filters'=>['search'=>$search],'stats'=>['total'=>MembershipPackage::count()]]); }
 public function show(MembershipPackage $membershipPackage) { $this->admin(); return Inertia::render('membership-packages/Show',['membershipPackage'=>$membershipPackage]); }
 public function store(Request $request){$this->admin(); MembershipPackage::create($request->validate(['name'=>'required|string|max:255','price'=>'required|numeric|min:0','duration_days'=>'required|integer|min:1','description'=>'nullable|string'])); return back()->with('success','Membership package berhasil dibuat.');}
 public function update(Request $request,MembershipPackage $membershipPackage){$this->admin(); $membershipPackage->update($request->validate(['name'=>'required|string|max:255','price'=>'required|numeric|min:0','duration_days'=>'required|integer|min:1','description'=>'nullable|string'])); return back()->with('success','Membership package berhasil diperbarui.');}
 public function destroy(MembershipPackage $membershipPackage){$this->admin();$membershipPackage->delete();return back()->with('success','Membership package berhasil dihapus.');}
}
