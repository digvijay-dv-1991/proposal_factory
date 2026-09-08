/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';

/**
 * Live-refresh the notification bell over the socket instead of polling —
 * Filament's own admin bell already listens for this same broadcast
 * itself, so this only needs to cover the front-end board's hand-rolled
 * bell (OpportunityBoard::refreshNotifications()).
 */
const userId = document.querySelector('meta[name="user-id"]')?.content;

if (userId && window.Echo) {
    window.Echo.private(`App.Models.User.${userId}`)
        .listen('.database-notifications.sent', () => {
            window.Livewire?.dispatch('notifications-updated');
        });
}
