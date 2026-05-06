<?php

namespace App\Livewire\Settings;

use App\Models\OutboundWebhook;
use App\Models\WebhookDelivery;
use App\Services\Webhooks\OutboundWebhookSender;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class WebhookManager extends Component
{
    use WithPagination;

    public bool $showEndpointForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $target_url = '';

    public string $http_method = 'POST';

    public string $secret_input = '';

    public string $auth_bearer_input = '';

    public bool $is_enabled = true;

    public string $payload_template = '';

    public string $mapping_rules_json = '{}';

    public string $allowed_cidrs_text = '';

    public bool $verify_ssl = true;

    public string $custom_headers_json = '{}';

    public bool $enforce_https = true;

    public ?int $inspectDeliveryId = null;

    public int $max_retries = 3;

    public int $timeout_seconds = 15;

    public int $sort_order = 0;

    /** @var list<string> */
    public array $selected_events = [];

    public function mount(): void
    {
        $user = auth()->user();
        if ($user === null || ! in_array(strtolower((string) $user->role), ['admin', 'super_admin'], true)) {
            abort(403);
        }

        $this->resetForm();
    }

    public function startCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showEndpointForm = true;
    }

    public function startEdit(int $id): void
    {
        $hook = OutboundWebhook::query()->findOrFail($id);
        $this->editingId = $hook->id;
        $this->showEndpointForm = true;
        $this->name = $hook->name;
        $this->target_url = $hook->target_url;
        $this->http_method = strtoupper($hook->http_method);
        $this->secret_input = '';
        $this->auth_bearer_input = '';
        $this->is_enabled = $hook->is_enabled;
        $this->payload_template = (string) ($hook->payload_template ?? '');
        $this->custom_headers_json = json_encode($hook->custom_headers ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->enforce_https = $hook->enforce_https;
        $this->verify_ssl = (bool) ($hook->verify_ssl ?? true);
        $this->mapping_rules_json = json_encode($hook->mapping_rules ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $cidrs = $hook->allowed_destination_cidrs ?? [];
        $this->allowed_cidrs_text = is_array($cidrs) ? implode("\n", array_map('strval', $cidrs)) : '';
        $this->max_retries = (int) $hook->max_retries;
        $this->timeout_seconds = (int) $hook->timeout_seconds;
        $this->sort_order = (int) $hook->sort_order;
        $this->selected_events = array_values($hook->events ?? []);
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showEndpointForm = false;
    }

    public function save(): void
    {
        if (trim($this->mapping_rules_json) === '') {
            $this->mapping_rules_json = '{}';
        }

        $catalog = $this->catalogEventKeys();

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'target_url' => ['required', 'string', 'max:2048'],
            'http_method' => ['required', Rule::in(['POST', 'GET', 'PUT', 'PATCH'])],
            'is_enabled' => ['boolean'],
            'payload_template' => ['nullable', 'string', 'max:65535'],
            'mapping_rules_json' => ['nullable', 'json'],
            'allowed_cidrs_text' => ['nullable', 'string', 'max:4000'],
            'verify_ssl' => ['boolean'],
            'custom_headers_json' => ['required', 'json'],
            'enforce_https' => ['boolean'],
            'max_retries' => ['required', 'integer', 'min:1', 'max:10'],
            'timeout_seconds' => ['required', 'integer', 'min:1', 'max:120'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
            'selected_events' => ['required', 'array', 'min:1'],
            'selected_events.*' => ['string', Rule::in($catalog)],
            'secret_input' => ['nullable', 'string', 'max:512'],
            'auth_bearer_input' => ['nullable', 'string', 'max:2048'],
        ];

        $validated = $this->validate($rules);

        if ($validated['enforce_https'] && ! str_starts_with(strtolower($validated['target_url']), 'https://')) {
            $this->addError('target_url', __('HTTPS URLs are required when enforcement is enabled.'));

            return;
        }

        $headersDecoded = json_decode($validated['custom_headers_json'], true);
        if (! is_array($headersDecoded)) {
            $this->addError('custom_headers_json', __('Custom headers must be a JSON object.'));

            return;
        }

        foreach ($headersDecoded as $hk => $hv) {
            if (! is_string($hk) || (! is_string($hv) && ! is_numeric($hv))) {
                $this->addError('custom_headers_json', __('Header keys must be strings and values string-like.'));

                return;
            }
        }

        $mappingDecoded = json_decode($validated['mapping_rules_json'] ?? '{}', true);
        if (! is_array($mappingDecoded)) {
            $this->addError('mapping_rules_json', __('Mapping rules must be a JSON object.'));

            return;
        }

        $mappingStored = $mappingDecoded === [] ? null : $mappingDecoded;

        $cidrLines = preg_split('/\r\n|\r|\n/', (string) ($validated['allowed_cidrs_text'] ?? '')) ?: [];
        $cidrList = array_values(array_filter(array_map('trim', $cidrLines), fn (string $s): bool => $s !== ''));

        $payload = [
            'name' => $validated['name'],
            'target_url' => $validated['target_url'],
            'http_method' => strtoupper($validated['http_method']),
            'is_enabled' => $validated['is_enabled'],
            'payload_template' => $validated['payload_template'] !== '' ? $validated['payload_template'] : null,
            'mapping_rules' => $mappingStored,
            'allowed_destination_cidrs' => $cidrList === [] ? null : $cidrList,
            'verify_ssl' => $validated['verify_ssl'],
            'custom_headers' => $headersDecoded,
            'enforce_https' => $validated['enforce_https'],
            'max_retries' => $validated['max_retries'],
            'timeout_seconds' => $validated['timeout_seconds'],
            'sort_order' => $validated['sort_order'],
            'events' => array_values(array_unique($validated['selected_events'])),
        ];

        if ($this->editingId !== null) {
            $hook = OutboundWebhook::query()->findOrFail($this->editingId);
            if ($validated['secret_input'] !== '') {
                $payload['secret'] = $validated['secret_input'];
            }
            if ($validated['auth_bearer_input'] !== '') {
                $payload['auth_bearer_token'] = $validated['auth_bearer_input'];
            }
            $hook->fill($payload);
            $hook->save();
            session()->flash('status', __('Webhook endpoint updated.'));
        } else {
            $payload['secret'] = $validated['secret_input'] !== '' ? $validated['secret_input'] : null;
            $payload['auth_bearer_token'] = $validated['auth_bearer_input'] !== '' ? $validated['auth_bearer_input'] : null;
            OutboundWebhook::query()->create($payload);
            session()->flash('status', __('Webhook endpoint created.'));
        }

        $this->cancelEdit();
    }

    public function toggleEnabled(int $id): void
    {
        $hook = OutboundWebhook::query()->findOrFail($id);
        $hook->forceFill(['is_enabled' => ! $hook->is_enabled])->save();
        session()->flash('status', __('Status updated.'));
    }

    public function deleteEndpoint(int $id): void
    {
        OutboundWebhook::query()->whereKey($id)->delete();
        session()->flash('status', __('Webhook endpoint removed.'));
        if ($this->editingId === $id) {
            $this->cancelEdit();
        }
    }

    public function sendTest(int $id, OutboundWebhookSender $sender): void
    {
        $hook = OutboundWebhook::query()->findOrFail($id);
        $sender->send($hook, 'integration.test', [
            'source' => 'markonminds',
            'test' => true,
            'triggered_at' => now()->toIso8601String(),
        ]);
        session()->flash('status', __('Test request sent (see delivery logs).'));
    }

    /**
     * @return list<string>
     */
    private function catalogEventKeys(): array
    {
        return collect(config('settings.webhook_events', []))
            ->pluck('key')
            ->filter()
            ->map(fn (mixed $k): string => (string) $k)
            ->values()
            ->all();
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->target_url = '';
        $this->http_method = 'POST';
        $this->secret_input = '';
        $this->auth_bearer_input = '';
        $this->is_enabled = true;
        $this->payload_template = '';
        $this->mapping_rules_json = '{}';
        $this->allowed_cidrs_text = '';
        $this->verify_ssl = true;
        $this->custom_headers_json = '{}';
        $this->enforce_https = true;
        $this->inspectDeliveryId = null;
        $this->max_retries = 3;
        $this->timeout_seconds = 15;
        $this->sort_order = 0;
        $this->selected_events = ['integration.test'];
        $this->resetValidation();
    }

    public function render(): View
    {
        if (! Schema::hasTable('outbound_webhooks')) {
            return view('livewire.settings.webhook-manager-unavailable');
        }

        $hooks = OutboundWebhook::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $deliveries = WebhookDelivery::query()
            ->with('outboundWebhook')
            ->latest()
            ->paginate(12);

        return view('livewire.settings.webhook-manager', [
            'hooks' => $hooks,
            'deliveries' => $deliveries,
            'catalogEvents' => config('settings.webhook_events', []),
            'inspectedDelivery' => $this->inspectDeliveryId !== null
                ? WebhookDelivery::query()->with('outboundWebhook')->find($this->inspectDeliveryId)
                : null,
        ]);
    }

    public function inspectDelivery(?int $id): void
    {
        $this->inspectDeliveryId = $id;
    }
}
