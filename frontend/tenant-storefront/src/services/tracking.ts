import { v4 as uuidv4 } from 'uuid';

const SESSION_ID_KEY = 'gxy_session_id';

/**
 * Retrieves the current session ID from sessionStorage, or creates a new one.
 * @returns {string} The session ID.
 */
function getSessionId(): string {
  let sessionId = sessionStorage.getItem(SESSION_ID_KEY);
  if (!sessionId) {
    sessionId = uuidv4();
    sessionStorage.setItem(SESSION_ID_KEY, sessionId);
  }
  return sessionId;
}

/**
 * Tracks a behavioral event by sending it to the backend API.
 *
 * @param {string} eventName - The name of the event (e.g., 'page_view', 'add_to_cart').
 * @param {object} [payload={}] - An optional object containing additional data about the event.
 */
export async function trackEvent(eventName: string, payload: object = {}): Promise<void> {
  const apiBaseUrl = import.meta.env.VITE_API_BASE_URL;
  if (!apiBaseUrl) {
    console.error('VITE_API_BASE_URL is not defined. Tracking is disabled.');
    return;
  }

  const endpoint = `${apiBaseUrl}/api/tenant/track`;
  const sessionId = getSessionId();

  const eventData = {
    session_id: sessionId,
    event_name: eventName,
    payload,
  };

  try {
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(eventData),
    });

    if (!response.ok) {
      const responseBody = await response.json();
      console.error('Failed to track event:', response.status, responseBody.message);
    }
  } catch (error) {
    console.error('Error while tracking event:', error);
  }
}
