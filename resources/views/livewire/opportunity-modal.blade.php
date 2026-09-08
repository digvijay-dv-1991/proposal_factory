<div>
    <div class="overlay" x-data x-on:click.self="$wire.close()">
        <div class="modal">
            <div class="modal-head">
                <h2>{{ $opportunity->exists ? 'Opportunity Details' : 'New Opportunity' }}</h2>
                <button type="button" class="close" wire:click="close" aria-label="Close">&times;</button>
            </div>

            <div class="modal-tabs">
                @foreach (\App\Livewire\OpportunityModal::TABS as $tab)
                    <button type="button" class="{{ $activeTab === $tab ? 'active' : '' }}" wire:click="selectTab('{{ $tab }}')">
                        {{ $tab }}
                        @if ($this->tabBadgeCount($tab) > 0)
                            <span class="bubble">{{ $this->tabBadgeCount($tab) }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            @if ($errors->any())
                <div class="modal-error-banner">
                    <strong>Please fix the following before saving:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="modal-body">
                @if ($activeTab === 'Overview')
                    <div>
                        <div class="panel">
                            <h3>Opportunity Information</h3>
                            <div class="fields">
                                <div class="field full">
                                    <label>Opportunity Name</label>
                                    <input type="text" wire:model="form.name" value="{{ $form['name'] }}">
                                    @error('form.name') <div class="source-required-note">{{ $message }}</div> @enderror
                                </div>
                                <div class="field">
                                    <label>Agency Name</label>
                                    <input type="text" wire:model="form.agency" value="{{ $form['agency'] }}">
                                    @error('form.agency') <div class="source-required-note">{{ $message }}</div> @enderror
                                </div>
                                <div class="field">
                                    <label>Agency Sub Section</label>
                                    <input type="text" wire:model="form.agency_subsection" value="{{ $form['agency_subsection'] }}">
                                </div>
                                <div class="field">
                                    <label>Solicitation Number</label>
                                    <input type="text" wire:model="form.solicitation" value="{{ $form['solicitation'] }}">
                                </div>
                                <div class="field">
                                    <label>NAICS</label>
                                    <input type="text" wire:model="form.naics" value="{{ $form['naics'] }}">
                                </div>
                                <div class="field">
                                    <label>PSC</label>
                                    <input type="text" wire:model="form.psc" value="{{ $form['psc'] }}">
                                </div>
                                <div class="field">
                                    <label>Estimated Value (USD)</label>
                                    <input type="number" step="0.01" min="0" class="currency-input" wire:model="form.value" value="{{ $form['value'] }}">
                                </div>
                                <div class="field">
                                    <label>Contract Vehicle</label>
                                    <input type="text" wire:model="form.vehicle" value="{{ $form['vehicle'] }}">
                                </div>
                                <div class="field">
                                    <label>Set-Aside</label>
                                    <select wire:model="form.set_aside">
                                        <option value="" @selected($form['set_aside'] === null)>Select set-aside</option>
                                        @if ($this->isNonStandardSetAside())
                                            <option value="{{ $form['set_aside'] }}" selected>{{ $form['set_aside'] }}</option>
                                        @endif
                                        @foreach (\App\Livewire\OpportunityModal::SET_ASIDE_OPTIONS as $option)
                                            <option value="{{ $option }}" @selected($form['set_aside'] === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field">
                                    <label>ALQIMI Section</label>
                                    <select wire:model="form.origin">
                                        @foreach (\App\Livewire\OpportunityBoard::SECTIONS as $section)
                                            <option value="{{ $section }}" @selected($form['origin'] === $section)>{{ $section }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field">
                                    <label>Acquisition Phase</label>
                                    <select wire:model="form.phase">
                                        @foreach (\App\Livewire\OpportunityBoard::PHASES as $phase)
                                            <option value="{{ $phase }}" @selected($form['phase'] === $phase)>{{ $phase }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field">
                                    <label>Alqimi's Decision</label>
                                    <select wire:model.live="form.decision">
                                        @foreach (\App\Livewire\OpportunityModal::DECISIONS as $decision)
                                            <option value="{{ $decision }}" @selected($form['decision'] === $decision)>{{ $decision }}</option>
                                        @endforeach
                                    </select>
                                    @error('form.decision') <div class="source-required-note">{{ $message }}</div> @enderror
                                </div>
                                <div class="field">
                                    <label>Date Added</label>
                                    <input type="date" wire:model="form.date_added" value="{{ $form['date_added'] }}">
                                </div>
                                <div class="field">
                                    <label>Date Due</label>
                                    <input type="date" wire:model="form.response_due" value="{{ $form['response_due'] }}">
                                </div>
                                <div class="field full">
                                    <label>Official RFP / Solicitation Source</label>
                                    <div class="source-field-row">
                                        <div class="field">
                                            <input type="url" wire:model="form.link" value="{{ $form['link'] }}" placeholder="Required: exact source or reference URL">
                                        </div>
                                        @if ($opportunity->link)
                                            <a class="source-link" href="{{ $opportunity->link }}" target="_blank" rel="noopener noreferrer">&#8599; Open source</a>
                                        @else
                                            <span class="source-link disabled">&#8599; Open source</span>
                                        @endif
                                    </div>
                                    @error('form.link') <div class="source-required-note">{{ $message }}</div> @enderror
                                    @if (! $errors->has('form.link'))
                                        <div class="source-required-note {{ $opportunity->has_required_source ? 'ok' : '' }}">
                                            {{ $opportunity->has_required_source ? '✓ Credible reference recorded' : 'Credible source required. Search-engine result pages are not accepted.' }}
                                        </div>
                                    @endif
                                </div>
                                <div class="field full">
                                    <label>GovWin Reference (Secondary)</label>
                                    <div class="source-field-row">
                                        <div class="field">
                                            <input type="url" wire:model="form.govwin_link" value="{{ $form['govwin_link'] }}" placeholder="Optional secondary GovWin reference">
                                        </div>
                                        @if ($opportunity->govwin_link)
                                            <a class="source-link" href="{{ $opportunity->govwin_link }}" target="_blank" rel="noopener noreferrer">&#8599; Open GovWin reference</a>
                                        @else
                                            <span class="source-link disabled">&#8599; Open GovWin reference</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="field full">
                                    <label>ALQIMI SME</label>
                                    <input type="text" wire:model="form.alqimi_sme" value="{{ $form['alqimi_sme'] }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="panel">
                            <h3>Capture Information</h3>
                            <div class="fields">
                                <div class="field">
                                    <label>ALQIMI Next Action</label>
                                    <input type="text" wire:model="form.next_action" value="{{ $form['next_action'] }}">
                                </div>
                                <div class="field">
                                    <label>Action Due</label>
                                    <input type="date" wire:model="form.action_due" value="{{ $form['action_due'] }}">
                                </div>
                            </div>
                        </div>

                        <div class="panel">
                            <h3>Core Narrative</h3>
                            <div class="fields">
                                <div class="field full">
                                    <label>Solicitation Key Points</label>
                                    @if (count($this->solicitationKeyPoints))
                                        <div class="solicitation-key-points"><ul>
                                            @foreach ($this->solicitationKeyPoints as $point)
                                                <li>{{ $point }}</li>
                                            @endforeach
                                        </ul></div>
                                    @else
                                        <div class="notice">No source Event Description is available to summarize.</div>
                                    @endif
                                </div>
                                <div class="field full">
                                    <label>Solicitation Description</label>
                                    <textarea wire:model="form.source_description">{{ $form['source_description'] }}</textarea>
                                </div>
                                <div class="field full">
                                    <label>Requirements Key Points</label>
                                    @if (count($this->requirementKeyPoints))
                                        <div class="requirements-key-points"><ul>
                                            @foreach ($this->requirementKeyPoints as $point)
                                                <li>{{ $point }}</li>
                                            @endforeach
                                        </ul></div>
                                    @else
                                        <div class="notice">No source requirements are available to summarize.</div>
                                    @endif
                                </div>
                                <div class="field full">
                                    <label>Solicitation Requirements</label>
                                    <textarea wire:model="form.source_requirements">{{ $form['source_requirements'] }}</textarea>
                                </div>
                                <div class="field full">
                                    <label class="gap-title">Primary Gap / Risk</label>
                                    <textarea wire:model="form.gap" placeholder="Explain why the gap matters, source requirement context, and mitigation approach.">{{ $form['gap'] }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="panel">
                            <h3>Bid / No Bid Discussion</h3>
                            <div class="bid-chat">
                                <div class="bid-chat-messages">
                                    @forelse ($bidComments as $comment)
                                        <div class="bid-chat-message">
                                            <div class="bid-chat-meta">
                                                <span class="bid-chat-name">{{ $comment['name'] }}</span>
                                                <span class="bid-chat-time" title="{{ $comment['created_at']->format('M j, Y g:i A') }}">{{ $comment['created_at']->diffForHumans() }}</span>
                                            </div>
                                            <div class="bid-chat-text">{{ $comment['text'] }}</div>
                                        </div>
                                    @empty
                                        <div class="notice">No comments yet. Be the first to weigh in on whether we should bid.</div>
                                    @endforelse
                                </div>
                                @if (! $opportunity->exists)
                                    <div class="notice">Save the opportunity to start the discussion.</div>
                                @else
                                    <div class="bid-chat-form">
                                        @unless (auth()->check())
                                            <div class="field">
                                                <label>Your name</label>
                                                <input type="text" wire:model="guestName" placeholder="Your name">
                                                @error('guestName') <div class="source-required-note">{{ $message }}</div> @enderror
                                            </div>
                                        @endunless
                                        <div class="field">
                                            <label>Comment</label>
                                            <textarea wire:model="newBidComment" placeholder="Should we bid on this? Share your take..."></textarea>
                                            @error('newBidComment') <div class="source-required-note">{{ $message }}</div> @enderror
                                        </div>
                                        <button type="button" class="btn primary bid-chat-post" wire:click="postBidComment">Post</button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if ($form['origin'] === 'CBRN')
                            <div class="panel">
                                <h3>ALQIMI Role in CBRN Opportunities</h3>
                                <div class="fit-box">
                                    <strong>Information advantage, not laboratory science.</strong>
                                    <p style="margin: 8px 0 0; line-height: 1.55">
                                        ALQIMI should lead requirements involving multi-source data integration, intelligence and
                                        information analytics, AI-enabled warning, decision support, operational workflows, and
                                        translation of information into military doctrine and operations. When the requirement
                                        includes laboratory research, epidemiology, diagnostics, therapeutics, medical devices, or
                                        specialized scientific validation, the capture strategy should identify a qualified
                                        science-domain teammate while preserving ALQIMI ownership of the information problem.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <aside class="summary-col">
                        <div class="panel">
                            <h3>Summary</h3>
                            <div class="summary-pwin" x-data="{ pwin: {{ (int) $form['probability'] }} }">
                                <div class="summary-pwin-title">Win Probability</div>
                                <div class="summary-pwin-control">
                                    <input
                                        type="range" min="0" max="100"
                                        wire:model="form.probability"
                                        value="{{ (int) $form['probability'] }}"
                                        x-on:input="pwin = Number($event.target.value)"
                                        x-bind:style="'--pwin: ' + pwin + '%'"
                                        aria-label="Win Probability"
                                    >
                                    <span
                                        class="summary-pwin-value pwin-badge"
                                        x-bind:class="pwin >= 70 ? 'pwin-high' : (pwin >= 50 ? 'pwin-mid' : 'pwin-low')"
                                        x-text="pwin + '%'"
                                    ></span>
                                </div>
                            </div>
                            <div class="summary-list">
                                <div class="summary-item"><span>Status</span><strong>{{ $opportunity->phase }}</strong></div>
                                <div class="summary-item"><span>Recommendation</span><strong>{{ $opportunity->decision }}</strong></div>
                                @if ($opportunity->decision === 'Bid')
                                    <div class="summary-item"><span>Bid Priority</span><strong>{{ $opportunity->bid_priority ? 'Priority '.$opportunity->bid_priority : 'Not selected' }}</strong></div>
                                @endif
                                <div class="summary-item"><span>Strategic Priority</span><strong>{{ $this->strategicPriorityLabel() }}</strong></div>
                                <div class="summary-item"><span>Set-Aside</span><strong>{{ $opportunity->set_aside ?: '—' }}</strong></div>
                                <div class="summary-item"><span>Eligibility</span><strong>{{ $this->eligibilityLabel() }}</strong></div>
                                <div class="summary-item"><span>Gap Analysis</span><strong>{{ trim((string) $opportunity->gap) !== '' ? 'Complete' : 'Needed' }}</strong></div>
                                <div class="summary-item"><span>Competition</span><strong>{{ $opportunity->competitive_position ?: 'Unknown' }}</strong></div>
                                <div class="summary-item"><span>Teaming</span><strong>{{ count($partners) ? 'In Progress' : 'Not Started' }}</strong></div>
                                <div class="summary-item"><span>Last Updated</span><strong>{{ $opportunity->date_added?->format('M j, Y') ?? '—' }}</strong></div>
                                @if ($opportunity->decision === 'Monitoring')
                                    <div class="summary-item"><span>Monitoring Last Checked</span><strong>{{ $opportunity->monitoring_last_checked?->format('M j, Y') ?? 'Not yet checked' }}</strong></div>
                                @endif
                            </div>
                        </div>

                        <div class="panel">
                            <h3>Accessible Links</h3>
                            @if (count($opportunity->accessible_links))
                                <div class="accessible-links-list">
                                    @foreach ($opportunity->accessible_links as $link)
                                        <div class="accessible-link-row">
                                            <div>
                                                <div class="accessible-link-name">{{ $link['label'] }}</div>
                                                <div class="accessible-link-url" title="{{ $link['url'] }}">{{ $link['url'] }}</div>
                                            </div>
                                            <a class="source-link accessible-link-open" href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">&#8599; Open</a>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="notice">No accessible URLs recorded for this opportunity.</div>
                            @endif
                        </div>

                        <div class="panel">
                            <h3>Source Attachments</h3>
                            @if ($opportunity->exists && $opportunity->attachments->count())
                                <div class="source-attachments-list">
                                    @foreach ($opportunity->attachments as $attachment)
                                        <div class="source-attachment-item">
                                            <div class="source-attachment-meta">
                                                <div class="source-attachment-name">📎 {{ $attachment->original_name }}</div>
                                                <div class="source-attachment-type">{{ $attachment->type ?: 'Source attachment' }}</div>
                                            </div>
                                            <a class="source-attachment-open" href="{{ $attachment->url ?: \Illuminate\Support\Facades\Storage::url($attachment->path) }}" target="_blank" rel="noopener noreferrer">Open</a>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="source-attachment-empty">No source attachments are currently recorded for this opportunity.</div>
                            @endif
                        </div>

                        <div class="panel competitor-links-panel">
                            <h3>Competitor Links</h3>
                            @if (count($opportunity->competitor_links))
                                <div class="competitor-links-list">
                                    @foreach ($opportunity->competitor_links as $link)
                                        <div class="competitor-link-item">
                                            <div class="competitor-link-meta">
                                                <div class="competitor-link-name">{{ $link['name'] }}</div>
                                                <div class="competitor-link-role">{{ $link['role'] }}</div>
                                            </div>
                                            <a class="competitor-link-open" href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">Website</a>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="competitor-links-empty">No named incumbent or competitor website is currently available for this opportunity.</div>
                            @endif
                        </div>
                    </aside>
                @elseif ($activeTab === 'Bid Decision')
                    <div class="tab-page active">
                        @if (! $opportunity->exists)
                            <div class="notice">Save the opportunity before inviting reviewers.</div>
                        @else
                            <div class="panel">
                                <h3>Invite a Reviewer</h3>
                                <p>Invite a teammate to look at the RFP and weigh in before a Bid / No Bid decision is made.</p>
                                <div class="inline-actions">
                                    <div class="field repeater-field-grow">
                                        <label>Team member</label>
                                        <select wire:model="inviteUserId">
                                            <option value="">Select a user to invite</option>
                                            @foreach ($this->inviteCandidates as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('inviteUserId') <div class="source-required-note">{{ $message }}</div> @enderror
                                    </div>
                                    <button type="button" class="btn primary" wire:click="inviteUser">+ Invite</button>
                                </div>
                            </div>

                            <div class="panel">
                                <h3>Invited Reviewers</h3>
                                <div class="fields">
                                    @forelse ($bidInvites as $invite)
                                        <div class="field full">
                                            <div class="repeater-row">
                                                <div class="field repeater-field-md">
                                                    <label>Reviewer</label>
                                                    <div>{{ $invite['name'] }}</div>
                                                </div>
                                                <div class="field repeater-field-md">
                                                    <label>Invited By</label>
                                                    <div>{{ $invite['invited_by'] }}</div>
                                                </div>
                                                <div class="field repeater-field-md">
                                                    <label>Last Invited</label>
                                                    <div>{{ $invite['last_invited_at']->diffForHumans() }}</div>
                                                </div>
                                                <div class="field repeater-field-actions">
                                                    <button type="button" class="btn" wire:click="reinviteUser({{ $invite['id'] }})">Reinvite</button>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="field full"><div class="notice">No reviewers invited yet.</div></div>
                                    @endforelse
                                </div>
                            </div>
                        @endif
                    </div>
                @elseif ($activeTab === 'Contracting Officers')
                    <div class="tab-page active">
                        @if (! $opportunity->exists)
                            <div class="notice">Save the opportunity before adding contracting officers.</div>
                        @else
                            <div class="panel">
                                <h3>Contracting Officers</h3>
                                <div class="fields">
                                    @foreach ($contacts as $index => $contact)
                                        <div class="field full">
                                            <div class="repeater-row">
                                                <div class="field repeater-field-md">
                                                    <label>Name</label>
                                                    <input type="text" wire:model="contacts.{{ $index }}.name" value="{{ $contact['name'] }}">
                                                    @error("contacts.{$index}.name") <div class="source-required-note">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="field repeater-field-md">
                                                    <label>Title</label>
                                                    <input type="text" wire:model="contacts.{{ $index }}.title" value="{{ $contact['title'] }}">
                                                </div>
                                                <div class="field repeater-field-md">
                                                    <label>Organization</label>
                                                    <input type="text" wire:model="contacts.{{ $index }}.organization" value="{{ $contact['organization'] }}">
                                                </div>
                                                <div class="field repeater-field-sm">
                                                    <label>Role</label>
                                                    <input type="text" wire:model="contacts.{{ $index }}.role" value="{{ $contact['role'] }}">
                                                </div>
                                                <div class="field repeater-field-md">
                                                    <label>Email</label>
                                                    <input type="email" wire:model="contacts.{{ $index }}.email" value="{{ $contact['email'] }}">
                                                    @error("contacts.{$index}.email") <div class="source-required-note">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="field repeater-field-md">
                                                    <label>Phone</label>
                                                    <input type="text" wire:model="contacts.{{ $index }}.phone" value="{{ $contact['phone'] }}">
                                                </div>
                                                <div class="field repeater-field-actions">
                                                    <button type="button" class="btn" wire:click="saveContact({{ $index }})">Save</button>
                                                    <button type="button" class="btn danger" wire:click="removeContact({{ $contact['id'] }})" wire:confirm="Remove this contact?">Remove</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="inline-actions repeater-row">
                                    <div class="field repeater-field-md"><label>Name</label><input type="text" wire:model="newContact.name" placeholder="New contact name"></div>
                                    <div class="field repeater-field-md"><label>Title</label><input type="text" wire:model="newContact.title"></div>
                                    <div class="field repeater-field-md"><label>Organization</label><input type="text" wire:model="newContact.organization"></div>
                                    <div class="field repeater-field-sm"><label>Role</label><input type="text" wire:model="newContact.role"></div>
                                    <div class="field repeater-field-md"><label>Email</label><input type="email" wire:model="newContact.email"></div>
                                    <div class="field repeater-field-md"><label>Phone</label><input type="text" wire:model="newContact.phone"></div>
                                    <button type="button" class="btn primary" wire:click="addContact">+ Add contact</button>
                                </div>
                                @error('newContact.name') <div class="source-required-note">{{ $message }}</div> @enderror
                            </div>
                        @endif
                    </div>
                @elseif ($activeTab === 'Updates')
                    <div class="tab-page active">
                        @if (! $opportunity->exists)
                            <div class="notice">Save the opportunity before logging updates.</div>
                        @else
                            <div class="panel">
                                <h3>Updates</h3>
                                <div class="fields">
                                    @forelse ($updatesList as $index => $update)
                                        <div class="field full">
                                            <div class="repeater-row">
                                                <div class="field repeater-field-sm">
                                                    <label>Date</label>
                                                    <input type="date" wire:model="updatesList.{{ $index }}.date" value="{{ $update['date'] }}">
                                                    @error("updatesList.{$index}.date") <div class="source-required-note">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="field repeater-field-grow">
                                                    <label>Update</label>
                                                    <textarea wire:model="updatesList.{{ $index }}.text">{{ $update['text'] }}</textarea>
                                                    @error("updatesList.{$index}.text") <div class="source-required-note">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="field repeater-field-actions">
                                                    <button type="button" class="btn" wire:click="saveUpdate({{ $index }})">Save</button>
                                                    <button type="button" class="btn danger" wire:click="removeUpdate({{ $update['id'] }})" wire:confirm="Remove this update?">Remove</button>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="field full"><div class="notice">No updates logged yet.</div></div>
                                    @endforelse
                                </div>
                                <div class="inline-actions repeater-row">
                                    <div class="field repeater-field-sm"><label>Date</label><input type="date" wire:model="newUpdate.date"></div>
                                    <div class="field repeater-field-grow"><label>Update</label><textarea wire:model="newUpdate.text" placeholder="What changed?"></textarea></div>
                                    <button type="button" class="btn primary" wire:click="addUpdate">+ Add update</button>
                                </div>
                            </div>
                        @endif
                    </div>
                @elseif ($activeTab === 'Gap Analysis')
                    <div class="tab-page active">
                        <div class="panel">
                            <h3>Gap Analysis</h3>
                            <div class="fields">
                                <div class="field full">
                                    <label>Primary Gap / Risk</label>
                                    <textarea wire:model="form.gap">{{ $form['gap'] }}</textarea>
                                </div>
                                <div class="field full">
                                    <label>Gap Mitigation</label>
                                    <textarea wire:model="form.gap_mitigation">{{ $form['gap_mitigation'] }}</textarea>
                                </div>
                                <div class="field">
                                    <label>Gap Owner</label>
                                    <input type="text" wire:model="form.gap_owner" value="{{ $form['gap_owner'] }}">
                                </div>
                                <div class="field">
                                    <label>Gap Status</label>
                                    <select wire:model="form.gap_status">
                                        @foreach (\App\Livewire\OpportunityModal::GAP_STATUSES as $status)
                                            <option value="{{ $status }}" @selected($form['gap_status'] === $status)>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif ($activeTab === 'Competitive Analysis')
                    <div class="tab-page active" @if ($generatingCompetitiveAnalysis) wire:poll.3s="pollCompetitiveAnalysis" @endif>
                        <div class="panel ai-generate-panel">
                            <div class="ai-generate-row">
                                <div>
                                    <h3>AI Research</h3>
                                    @if ($opportunity->competitive_analysis_generated_at)
                                        <p class="ai-generated-note">Last generated {{ $opportunity->competitive_analysis_generated_at->diffForHumans() }} — every fact below is sourced, see Sources Found.</p>
                                    @else
                                        <p class="ai-generated-note">Not generated yet. AI will search the web and fill every field below, citing sources — anything it can't verify is left blank.</p>
                                    @endif
                                </div>
                                <button type="button" class="btn primary" wire:click="generateCompetitiveAnalysis" wire:loading.attr="disabled" wire:target="generateCompetitiveAnalysis" @disabled($generatingCompetitiveAnalysis)>
                                    @if ($generatingCompetitiveAnalysis)
                                        Researching&hellip;
                                    @elseif ($opportunity->competitive_analysis_generated_at)
                                        Regenerate with AI
                                    @else
                                        Generate with AI
                                    @endif
                                </button>
                            </div>
                            @error('competitiveAnalysis') <div class="source-required-note">{{ $message }}</div> @enderror
                            @if (count($opportunity->competitive_analysis_sources ?? []))
                                <div class="ai-sources">
                                    <strong>Sources found</strong>
                                    <ul>
                                        @foreach ($opportunity->competitive_analysis_sources as $source)
                                            <li><a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer">{{ $source['label'] }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        <div class="panel">
                            <h3>Competitive Analysis</h3>
                            <div class="fields">
                                <div class="field">
                                    <label>Competitive Position</label>
                                    <select wire:model="form.competitive_position">
                                        @foreach (\App\Livewire\OpportunityModal::COMPETITIVE_POSITIONS as $position)
                                            <option value="{{ $position }}" @selected($form['competitive_position'] === $position)>{{ $position }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field full">
                                    <label>Competitive Analysis</label>
                                    <textarea wire:model="form.competitive_analysis">{{ $form['competitive_analysis'] }}</textarea>
                                </div>
                                <div class="field full">
                                    <label>Competitive Discriminators</label>
                                    <textarea wire:model="form.competitive_discriminators">{{ $form['competitive_discriminators'] }}</textarea>
                                </div>
                                <div class="field full">
                                    <label>Competitive Next Action</label>
                                    <textarea wire:model="form.competitive_next_action">{{ $form['competitive_next_action'] }}</textarea>
                                </div>
                                <div class="field full">
                                    <label>Known / Likely Competitors</label>
                                    <textarea wire:model="form.competitors">{{ $form['competitors'] }}</textarea>
                                </div>
                                <div class="field full">
                                    <label>Recommended Teaming Strategy</label>
                                    <textarea wire:model="form.teaming">{{ $form['teaming'] }}</textarea>
                                </div>
                                <div class="field full">
                                    <label>RFP Response Instructions</label>
                                    <textarea wire:model="form.rfp_instructions">{{ $form['rfp_instructions'] }}</textarea>
                                </div>
                                <div class="field full">
                                    <label>Required RFP Sections / Questions</label>
                                    <textarea wire:model="form.rfp_sections">{{ $form['rfp_sections'] }}</textarea>
                                </div>
                                <div class="field full">
                                    <label>Page Limit / Formatting</label>
                                    <input type="text" wire:model="form.rfp_format" value="{{ $form['rfp_format'] }}">
                                </div>
                                <div class="field full">
                                    <label>Evaluation Factors</label>
                                    <textarea wire:model="form.evaluation_factors">{{ $form['evaluation_factors'] }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="panel">
                            <h3>Incumbent</h3>
                            <div class="fields">
                                <div class="field">
                                    <label>Incumbent</label>
                                    <input type="text" wire:model="form.incumbent" value="{{ $form['incumbent'] }}">
                                </div>
                                <div class="field">
                                    <label>Incumbent Contract</label>
                                    <input type="text" wire:model="form.incumbent_contract" value="{{ $form['incumbent_contract'] }}">
                                </div>
                                <div class="field">
                                    <label>Incumbent Award Value</label>
                                    <input type="text" wire:model="form.incumbent_award_value" value="{{ $form['incumbent_award_value'] }}">
                                </div>
                                <div class="field">
                                    <label>Incumbent Period</label>
                                    <input type="text" wire:model="form.incumbent_period" value="{{ $form['incumbent_period'] }}">
                                </div>
                                <div class="field full">
                                    <label>Incumbent Brief</label>
                                    <textarea wire:model="form.incumbent_brief">{{ $form['incumbent_brief'] }}</textarea>
                                </div>
                                <div class="field full">
                                    <label>Incumbent Performance</label>
                                    <textarea wire:model="form.incumbent_performance">{{ $form['incumbent_performance'] }}</textarea>
                                </div>
                                <div class="field full">
                                    <label>Incumbent Strengths</label>
                                    <textarea wire:model="form.incumbent_strengths">{{ $form['incumbent_strengths'] }}</textarea>
                                </div>
                                <div class="field full">
                                    <label>Incumbent Weaknesses</label>
                                    <textarea wire:model="form.incumbent_weaknesses">{{ $form['incumbent_weaknesses'] }}</textarea>
                                </div>
                                <div class="field full">
                                    <label>Incumbent Customer Relationship</label>
                                    <textarea wire:model="form.incumbent_customer_relationship">{{ $form['incumbent_customer_relationship'] }}</textarea>
                                </div>
                                <div class="field">
                                    <label>Incumbent Source</label>
                                    <input type="text" wire:model="form.incumbent_source" value="{{ $form['incumbent_source'] }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif ($activeTab === 'Teaming')
                    <div class="tab-page active">
                        @if (! $opportunity->exists)
                            <div class="notice">Save the opportunity before adding teaming partners.</div>
                        @else
                            <div class="panel">
                                <h3>Teaming</h3>
                                <div class="fields">
                                    @foreach ($partners as $index => $partner)
                                        <div class="field full">
                                            <div class="repeater-row">
                                                <div class="field repeater-field-md">
                                                    <label>Company</label>
                                                    <input type="text" wire:model="partners.{{ $index }}.company" value="{{ $partner['company'] }}">
                                                    @error("partners.{$index}.company") <div class="source-required-note">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="field repeater-field-sm">
                                                    <label>Role</label>
                                                    <input type="text" wire:model="partners.{{ $index }}.role" value="{{ $partner['role'] }}">
                                                </div>
                                                <div class="field repeater-field-sm">
                                                    <label>Status</label>
                                                    <input type="text" wire:model="partners.{{ $index }}.status" value="{{ $partner['status'] }}">
                                                </div>
                                                <div class="field repeater-field-md">
                                                    <label>Capability</label>
                                                    <input type="text" wire:model="partners.{{ $index }}.capability" value="{{ $partner['capability'] }}">
                                                </div>
                                                <div class="field repeater-field-grow">
                                                    <label>Rationale</label>
                                                    <input type="text" wire:model="partners.{{ $index }}.rationale" value="{{ $partner['rationale'] }}">
                                                </div>
                                                <div class="field repeater-field-actions">
                                                    <button type="button" class="btn" wire:click="savePartner({{ $index }})">Save</button>
                                                    <button type="button" class="btn danger" wire:click="removePartner({{ $partner['id'] }})" wire:confirm="Remove this partner?">Remove</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="inline-actions repeater-row">
                                    <div class="field repeater-field-md"><label>Company</label><input type="text" wire:model="newPartner.company" placeholder="New partner company"></div>
                                    <div class="field repeater-field-sm"><label>Role</label><input type="text" wire:model="newPartner.role"></div>
                                    <div class="field repeater-field-sm"><label>Status</label><input type="text" wire:model="newPartner.status"></div>
                                    <div class="field repeater-field-md"><label>Capability</label><input type="text" wire:model="newPartner.capability"></div>
                                    <div class="field repeater-field-grow"><label>Rationale</label><input type="text" wire:model="newPartner.rationale"></div>
                                    <button type="button" class="btn primary" wire:click="addPartner">+ Add partner</button>
                                </div>
                                @error('newPartner.company') <div class="source-required-note">{{ $message }}</div> @enderror
                            </div>
                        @endif
                    </div>
                @elseif ($activeTab === 'Attachments')
                    <div class="tab-page active">
                        @if (! $opportunity->exists)
                            <div class="notice">Save the opportunity before adding attachments.</div>
                        @else
                            <div class="panel">
                                <h3>Attachments</h3>
                                @if ($opportunity->attachments->count())
                                    <div class="source-attachments-list">
                                        @foreach ($opportunity->attachments as $attachment)
                                            <div class="source-attachment-item">
                                                <div class="source-attachment-meta">
                                                    <div class="source-attachment-name">📎 {{ $attachment->original_name }}</div>
                                                    <div class="source-attachment-type">
                                                        {{ $attachment->type ?: 'Source attachment' }}
                                                        @if ($attachment->size)
                                                            · {{ number_format($attachment->size / 1024, 0) }} KB
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="repeater-field-actions">
                                                    <a class="btn" href="{{ $attachment->url ?: \Illuminate\Support\Facades\Storage::url($attachment->path) }}" target="_blank" rel="noopener noreferrer">Open</a>
                                                    <button type="button" class="btn danger" wire:click="removeAttachment({{ $attachment->id }})" wire:confirm="Delete this attachment?">Delete</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="source-attachment-empty">No attachments uploaded yet.</div>
                                @endif

                                <div class="inline-actions">
                                    <div class="field repeater-field-grow">
                                        <label>Upload a file (PDF, Word, Excel, PowerPoint, PNG, JPG — up to 10MB)</label>
                                        <input type="file" wire:model="newAttachmentFile">
                                        @error('newAttachmentFile') <div class="source-required-note">{{ $message }}</div> @enderror
                                        <div wire:loading wire:target="newAttachmentFile" class="source-required-note">Uploading…</div>
                                    </div>
                                    <button type="button" class="btn primary" wire:click="uploadAttachment" wire:loading.attr="disabled" wire:target="uploadAttachment">Upload</button>
                                </div>
                            </div>
                        @endif
                    </div>
                @elseif ($activeTab === 'AI Analysis')
                    <div class="tab-page active" @if ($generatingAiAnalysis) wire:poll.3s="pollAiAnalysis" @endif>
                        <div class="panel ai-generate-panel">
                            <div class="ai-generate-row">
                                <div>
                                    <h3>AI Analysis</h3>
                                    @if ($opportunity->ai_analysis_generated_at)
                                        <p class="ai-generated-note">Last generated {{ $opportunity->ai_analysis_generated_at->diffForHumans() }} — written from the fields already recorded on this opportunity.</p>
                                    @else
                                        <p class="ai-generated-note">Not generated yet.</p>
                                    @endif
                                </div>
                                <button type="button" class="btn primary" wire:click="generateAiAnalysis" wire:loading.attr="disabled" wire:target="generateAiAnalysis" @disabled($generatingAiAnalysis)>
                                    @if ($generatingAiAnalysis)
                                        Generating&hellip;
                                    @elseif ($opportunity->ai_analysis_generated_at)
                                        Regenerate with AI
                                    @else
                                        Generate with AI
                                    @endif
                                </button>
                            </div>
                            @error('aiAnalysis') <div class="source-required-note">{{ $message }}</div> @enderror
                        </div>

                        <div class="panel">
                            <h3>Executive Summary</h3>
                            <p>{{ $opportunity->ai_executive_summary ?: ($opportunity->description ?: 'No description recorded yet.') }}</p>
                        </div>
                        <div class="panel">
                            <h3>Why This Matters</h3>
                            <p>
                                @if ($opportunity->ai_why_it_matters)
                                    {{ $opportunity->ai_why_it_matters }}
                                @else
                                    This opportunity aligns with
                                    {{ ($opportunity->focus ?? []) !== [] ? implode(', ', $opportunity->focus) : 'no recorded' }}
                                    ALQIMI focus area(s), with a Bid Strength score of {{ $opportunity->go_strength }}% ({{ $opportunity->fit_label }}).
                                @endif
                            </p>
                        </div>
                        <div class="panel">
                            <h3>Red-Team Critique</h3>
                            <p>{{ $opportunity->ai_red_team_critique ?: ($opportunity->gap ?: 'No gap or risk analysis recorded yet.') }}</p>
                        </div>
                        <div class="panel">
                            <h3>Competitive Outlook</h3>
                            <p>
                                @if ($opportunity->ai_competitive_outlook)
                                    {{ $opportunity->ai_competitive_outlook }}
                                @else
                                    Incumbent: {{ $opportunity->incumbent ?: 'Unknown' }}.
                                    Position: {{ $opportunity->competitive_position ?: 'Unknown' }}.
                                    {{ $opportunity->competitive_analysis ?: 'No competitive analysis recorded yet.' }}
                                @endif
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="modal-foot">
                @if ($opportunity->exists)
                    <button type="button" class="btn danger" wire:click="delete" wire:confirm="Delete this opportunity? This cannot be undone.">
                        Delete opportunity
                    </button>
                @else
                    <span></span>
                @endif
                <div class="right">
                    <button type="button" class="btn" disabled title="Coming in Day 8 with exports">&#8595; HATCH Report</button>
                    <button type="button" class="btn" disabled title="Coming in Day 8 with exports">&#8595; Formal Response</button>
                    <button type="button" class="btn" wire:click="close" wire:loading.attr="disabled" wire:target="save">Cancel</button>
                    <button type="button" class="btn primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Save opportunity</span>
                        <span wire:loading wire:target="save">Saving&hellip;</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if ($showDecisionDialog)
        <div class="decision-overlay" x-data x-on:click.self="$wire.cancelDecision()">
            <div class="decision-dialog" role="dialog" aria-modal="true" aria-labelledby="decisionDialogTitle">
                <h3 id="decisionDialogTitle">Authorize {{ $pendingDecision }}</h3>
                <p>
                    {{ $pendingDecision === 'Bid'
                        ? 'Bid is a formal corporate decision. Select a priority, then record who ordered it and why.'
                        : 'No Bid is a formal corporate decision. Record who ordered it and why.' }}
                </p>

                @if ($pendingDecision === 'Bid')
                    <div class="field">
                        <label>Bid Priority *</label>
                        <select wire:model="decisionPriorityInput">
                            <option value="">Select priority</option>
                            <option value="1">Priority 1 — Top priority: open to COTS engagement</option>
                            <option value="2">Priority 2 — Mid priority: services opportunity that could lead to COTS engagement</option>
                            <option value="3">Priority 3 — Low priority: pure GOTS/services-only opportunity</option>
                        </select>
                        @error('decisionPriorityInput') <div class="source-required-note">{{ $message }}</div> @enderror
                        <div class="priority-help">
                            <div><strong>Priority 1:</strong> Open to COTS engagement.</div>
                            <div><strong>Priority 2:</strong> Pure services opportunity with potential to lead to COTS engagement.</div>
                            <div><strong>Priority 3:</strong> Pure GOTS opportunity; services only.</div>
                        </div>
                    </div>
                @endif

                <div class="field">
                    <label>Who ordered this? *</label>
                    <input type="text" wire:model="decisionWho" autocomplete="name" placeholder="Name and title">
                    @error('decisionWho') <div class="source-required-note">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label>Reason / decision rationale *</label>
                    <textarea wire:model="decisionReason" placeholder="Explain why this opportunity is being designated Bid or No Bid."></textarea>
                    @error('decisionReason') <div class="source-required-note">{{ $message }}</div> @enderror
                </div>

                <div class="decision-dialog-actions">
                    <button type="button" class="btn" wire:click="cancelDecision">Cancel</button>
                    <button type="button" class="btn primary" wire:click="confirmDecision">Record decision</button>
                </div>
            </div>
        </div>
    @endif
</div>
