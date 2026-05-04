<x-operations.workspace
    :page-title="__('Booking — :name', ['name' => $lead->name])"
    :welcome-line="__('Source, follow-up, and note history.')"
>
    @livewire('operations.bookings-show', ['lead' => $lead], key('bookings-show-'.$lead->id))
</x-operations.workspace>
