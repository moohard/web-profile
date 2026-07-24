<h1>Pesan kontak baru</h1>

<p><strong>Nama:</strong> {{ $contactMessage->name }}</p>
<p><strong>Email:</strong> {{ $contactMessage->email }}</p>

@if ($contactMessage->phone)
<p><strong>Telepon:</strong> {{ $contactMessage->phone }}</p>
@endif

@if ($contactMessage->subject)
<p><strong>Subjek:</strong> {{ $contactMessage->subject }}</p>
@endif

<p><strong>Pesan:</strong></p>
<p>{!! nl2br(e($contactMessage->message)) !!}</p>
