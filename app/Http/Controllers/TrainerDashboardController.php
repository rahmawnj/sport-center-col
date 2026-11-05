<?php
namespace App\Http\Controllers;
use App\Models\MemberTrainerPackage;
use App\Models\Trainer;
use App\Models\TrainerPackage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class TrainerDashboardController extends Controller
{
 private function trainerFor(Request $request): Trainer { return Trainer::where('user_id',$request->user()->id)->firstOrFail(); }
 public function index(Request $request): Response {
  $trainer=$this->trainerFor($request);
  $packages=MemberTrainerPackage::with(['user:id,name,email','trainerPackage:id,name','facility:id,name'])->where('trainer_id',$trainer->id)->latest()->get();
  return Inertia::render('trainer/Dashboard',[
   'trainer'=>['name'=>$trainer->name,'specialty'=>$trainer->specialty,'facilities'=>$trainer->facilities()->pluck('name')->all()],
   'stats'=>['total_members'=>$packages->whereNotIn('status',['cancelled'])->unique('user_id')->count(),'active_packages'=>$packages->where('status','active')->count(),'completed_sessions'=>(int)$packages->sum('used_sessions'),'remaining_sessions'=>(int)$packages->where('status','active')->sum(fn($p)=>max(0,$p->total_sessions-$p->used_sessions))],
   'recent_members'=>$packages->take(5)->map(fn($p)=>['id'=>$p->id,'member'=>$p->user?->name??'Member','package'=>$p->trainerPackage?->name??'Paket Trainer','facility'=>$p->facility?->name,'used_sessions'=>$p->used_sessions,'total_sessions'=>$p->total_sessions,'status'=>$p->status])->values()
  ]);
 }
 public function members(Request $request): Response {
  $trainer=$this->trainerFor($request);
  $assignedPackages=TrainerPackage::with('facilities:id,name')->whereHas('trainers',fn($query)=>$query->where('trainers.id',$trainer->id))->orderBy('name')->get()->map(fn($package)=>['id'=>$package->id,'name'=>$package->name,'sports'=>$package->facilities->pluck('name')->all(),'total_sessions'=>$package->total_sessions,'duration_days'=>$package->duration_days,'price'=>(float)$package->price,'is_active'=>$package->is_active]);
  $members=MemberTrainerPackage::with(['user:id,name,email','trainerPackage:id,name','facility:id,name'])->where('trainer_id',$trainer->id)->latest()->paginate(10)->withQueryString()->through(fn($p)=>['id'=>$p->id,'member'=>$p->user?->name??'Member','email'=>$p->user?->email,'package'=>$p->trainerPackage?->name??'Paket Trainer','facility'=>$p->facility?->name,'start_date'=>$p->start_date?->toDateString(),'expires_at'=>$p->expires_at?->toDateString(),'used_sessions'=>$p->used_sessions,'total_sessions'=>$p->total_sessions,'remaining_sessions'=>max(0,$p->total_sessions-$p->used_sessions),'status'=>$p->status]);
  return Inertia::render('trainer/Members',['members'=>$members,'assignedPackages'=>$assignedPackages]);
 }
 public function profile(Request $request): Response {
  $trainer=$this->trainerFor($request)->load(['user:id,name,email','facilities:id,name']);
  return Inertia::render('trainer/Profile',['profile'=>['name'=>$trainer->name,'email'=>$trainer->user?->email,'phone'=>$trainer->phone,'specialty'=>$trainer->specialty,'facilities'=>$trainer->facilities->pluck('name')->all()]]);
 }
}