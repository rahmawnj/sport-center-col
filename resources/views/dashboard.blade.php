@extends('layouts.app')
@section('title','Dashboard')
@section('heading','Dashboard')
@section('content')
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
@foreach([['Pengguna',$stats['users']],['Booking',$stats['bookings']],['Zona',$stats['zones']],['Space',$stats['spaces']],['Menunggu',$stats['pending']]] as $stat)
<div class="rounded-xl border bg-card p-5"><p class="text-sm text-muted-foreground">{{ $stat[0] }}</p><p class="mt-2 text-2xl font-bold">{{ $stat[1] }}</p></div>
@endforeach
</div>
<div class="mt-8 rounded-xl border bg-card"><div class="border-b p-5"><h2 class="font-semibold">Booking terbaru</h2></div><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left"><th class="p-4">Kode</th><th class="p-4">Nama</th><th class="p-4">Status</th><th class="p-4">Pembayaran</th><th class="p-4">Total</th></tr></thead><tbody>@forelse($recentBookings as $booking)<tr class="border-b last:border-0"><td class="p-4 font-medium">{{ $booking->booking_code }}</td><td class="p-4">{{ $booking->guest_name }}</td><td class="p-4">{{ $booking->booking_status ?: '-' }}</td><td class="p-4">{{ $booking->payment_status }}</td><td class="p-4">Rp {{ number_format($booking->total_amount,0,',','.') }}</td></tr>@empty<tr><td colspan="5" class="p-6 text-center text-muted-foreground">Belum ada booking.</td></tr>@endforelse</tbody></table></div></div>
@endsection
