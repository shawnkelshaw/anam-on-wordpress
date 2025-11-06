console.log('🎯 Anam Avatar - Starting...');

import { createClient, AnamEvent } from "https://esm.sh/@anam-ai/js-sdk@3.5.1/es2022/js-sdk.mjs";

// ANAM_FRONTEND_CONFIG is passed from WordPress via wp_localize_script
const ANAM_CONFIG = window.ANAM_FRONTEND_CONFIG || {};

let anamClient = null;
let conversationTranscript = [];
let currentSessionId = null;

// Function to update transcript debug box
function updateTranscriptDebugBox(messages) {
    const transcriptMessages = document.getElementById('transcript-messages');
    if (!transcriptMessages) {
        console.log('⚠️ Transcript debug box not found');
        return;
    }
    
    if (!messages || messages.length === 0) {
        transcriptMessages.innerHTML = '<em style="color: #999;">Waiting for conversation to start...</em>';
        return;
    }
    
    let html = '';
    messages.forEach((msg, index) => {
        const isUser = msg.type === 'user' || msg.role === 'user';
        const bgColor = isUser ? '#e3f2fd' : '#f1f8e9';
        const label = isUser ? '👤 User' : '🤖 Avatar';
        const text = msg.text || msg.content || msg.message || '';
        
        html += `<div style="margin-bottom: 10px; padding: 8px; background: ${bgColor}; border-radius: 6px;">
            <div style="font-weight: bold; font-size: 11px; color: #666; margin-bottom: 4px;">${label}</div>
            <div style="color: #333;">${text}</div>
        </div>`;
    });
    
    transcriptMessages.innerHTML = html;
    
    // Auto-scroll to bottom
    const transcriptDebug = document.getElementById('transcript-debug');
    if (transcriptDebug) {
        transcriptDebug.scrollTop = transcriptDebug.scrollHeight;
    }
}

// Missing function that's being called - add placeholder
function showElementTokenIcon(success) {
    console.log('showElementTokenIcon called with:', success);
    // This function was being called but didn't exist, causing the ReferenceError
}

// Helper function to extract session ID from token (if possible)
function extractSessionIdFromToken(token) {
    try {
        // JWT tokens have 3 parts separated by dots
        const parts = token.split('.');
        if (parts.length === 3) {
            // Decode the payload (second part)
            const payload = JSON.parse(atob(parts[1]));
            console.log('🔍 Token payload:', payload);
            
            // Look for session ID in various possible fields
            const sessionId = payload.sessionId || payload.session_id || payload.sid || payload.sub || payload.jti || null;
            console.log('🆔 Extracted session ID:', sessionId);
            return sessionId;
        }
    } catch (error) {
        console.log('⚠️ Could not decode token:', error);
    }
    return null;
}

// Alternative: Use Anam client session ID directly
function getSessionIdFromClient() {
    try {
        if (anamClient && anamClient.sessionId) {
            console.log('🎯 Using client session ID:', anamClient.sessionId);
            return anamClient.sessionId;
        }
        if (anamClient && anamClient.session && anamClient.session.id) {
            console.log('🎯 Using client session.id:', anamClient.session.id);
            return anamClient.session.id;
        }
    } catch (error) {
        console.log('⚠️ Could not get session ID from client:', error);
        console.error('❌ Error extracting session ID from token:', error);
    }
    return null;
}

// Function to save transcript to database
async function saveTranscriptToDatabase(sessionId) {
    if (!sessionId) {
        console.log('📝 No session ID to save transcript');
        return;
    }
    
    if (!conversationTranscript || conversationTranscript.length === 0) {
        console.log('📝 No transcript data to save');
        return;
    }
    
    try {
        console.log('💾 Saving transcript to database...');
        console.log('📋 Session ID:', sessionId);
        console.log('💬 Messages:', conversationTranscript.length);
        
        // Clean the transcript data - convert to plain array
        const cleanTranscript = Array.isArray(conversationTranscript) 
            ? conversationTranscript.map(msg => ({
                type: msg.type || msg.role || 'unknown',
                text: msg.text || msg.content || msg.message || '',
                timestamp: msg.timestamp || new Date().toISOString()
            }))
            : [];
        
        console.log('📦 Clean transcript:', cleanTranscript);
        
        const response = await fetch(ANAM_CONFIG.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'anam_save_transcript',
                nonce: ANAM_CONFIG.nonce,
                session_id: sessionId,
                transcript_data: JSON.stringify(cleanTranscript)
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            console.log('✅ Transcript saved successfully:', data.data);
        } else {
            console.error('❌ Failed to save transcript:', data.data);
        }
    } catch (error) {
        console.error('❌ Error saving transcript:', error);
    }
}

// Real-time message sending to server
async function sendMessageToServer(messageData) {
    if (!currentSessionId) {
        console.log('⚠️ No session ID available for message sending');
        return;
    }
    
    try {
        console.log('📤 Sending message to server:', messageData);
        console.log('📤 Session ID:', currentSessionId);
        console.log('📤 Nonce:', ANAM_CONFIG.nonce);
        
        const response = await fetch(ANAM_CONFIG.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'anam_store_message',
                nonce: ANAM_CONFIG.nonce,
                session_id: currentSessionId,
                message_data: JSON.stringify(messageData),
                timestamp: messageData.timestamp
            })
        });
        
        console.log('📤 Response status:', response.status);
        const data = await response.json();
        console.log('📤 Response data:', data);
        
        if (data.success) {
            console.log('✅ Message stored:', data.data);
        } else {
            console.error('❌ Message storage error:', data.data);
        }
    } catch (error) {
        console.error('❌ Failed to send message to server:', error);
    }
}

// Mark conversation as complete
async function markConversationComplete() {
    if (!currentSessionId) return;
    
    try {
        console.log('🏁 Marking conversation complete for session:', currentSessionId);
        
        const response = await fetch(ANAM_CONFIG.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'anam_conversation_complete',
                nonce: ANAM_CONFIG.nonce,
                session_id: currentSessionId,
                transcript: JSON.stringify(conversationTranscript)
            })
        });
        
        const data = await response.json();
        if (data.success) {
            console.log('✅ Conversation marked complete:', data.data);
        } else {
            console.error('❌ Conversation completion error:', data.data);
        }
    } catch (error) {
        console.error('❌ Failed to mark conversation complete:', error);
    }
}

// Finalize conversation (session ended)
async function finalizeConversation() {
    if (!currentSessionId) return;
    
    try {
        console.log('🔚 Finalizing conversation for session:', currentSessionId);
        
        // Send final transcript and trigger parsing
        const response = await fetch(ANAM_CONFIG.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'anam_finalize_conversation',
                nonce: ANAM_CONFIG.nonce,
                session_id: currentSessionId,
                transcript: JSON.stringify(conversationTranscript)
            })
        });
        
        const data = await response.json();
        if (data.success) {
            console.log('✅ Conversation finalized and parsed:', data.data);
        } else {
            console.error('❌ Conversation finalization error:', data.data);
        }
    } catch (error) {
        console.error('❌ Failed to finalize conversation:', error);
    }
}

// Handle widget button interactions
if (ANAM_CONFIG.displayMethod === 'page_position') {
    const widget = document.getElementById('anam-avatar-widget');
    const startBtn = document.getElementById('anam-start-btn');
    
    // Start conversation button
    startBtn.addEventListener('click', () => {
        initAvatar();
    });
} else {
    // For element_id method, show welcome screen in container
    showElementIdWelcome();
}

function updateStatus(message, isError = false) {
    console.log(isError ? '❌' : '🎯', message.replace(/<[^>]*>/g, ''));
}

function showElementIdWelcome() {
    console.log('🎯 Setting up Element ID welcome screen...');
    
    const targetElement = ANAM_CONFIG.containerId;
    
    if (!targetElement) {
        console.error('❌ Element ID method selected but no container ID specified');
        return;
    }
    
    const customContainer = document.getElementById(targetElement);
    if (!customContainer) {
        console.error(`❌ Container "${targetElement}" not found on this page`);
        return;
    }
    
    // Show welcome screen in the custom container
    customContainer.innerHTML = `
        <div style="padding: 20px; text-align: center; position: relative; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
            <div style="font-size: 32px; margin-bottom: 15px;">💬</div>
            <h3 style="margin: 0 0 10px 0; color: #333; font-size: 16px;">Ready to chat?</h3>
            <p style="margin: 0 0 20px 0; color: #666; font-size: 14px; line-height: 1.4;">
                Start a conversation with our AI assistant
            </p>
            <button id="anam-element-start-btn" style="padding: 12px 24px; border: none; background: #007cba; color: white; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: bold;">
                Start Conversation
            </button>
        </div>
    `;
    
    // Set container positioning for buttons
    customContainer.style.position = 'relative';
    
    // Add start button event listener
    const startBtn = document.getElementById('anam-element-start-btn');
    startBtn.addEventListener('click', () => {
        initElementIdAvatar();
    });
    
    console.log('✅ Element ID welcome screen ready');
}

async function initElementIdAvatar() {
    console.log('🎯 Initializing Element ID avatar...');
    
    const targetElement = ANAM_CONFIG.containerId;
    const customContainer = document.getElementById(targetElement);
    
    if (!customContainer) {
        console.error(`❌ Container "${targetElement}" not found`);
        return;
    }
    
    try {
        // Show loading state
        customContainer.innerHTML = `
            <div style="padding: 40px 20px; text-align: center; background: #f8f9fa; border-radius: 12px;">
                <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #e3e3e3; border-top: 4px solid #007cba; border-radius: 50%; animation: anam-spin 1s linear infinite; margin-bottom: 15px;"></div>
                <div style="color: #666; font-size: 14px;">Setting up your avatar...</div>
                <div style="color: #999; font-size: 12px; margin-top: 5px;">This may take a moment</div>
            </div>
            <style>
                @keyframes anam-spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        `;
        
        // Get session token and create client
        const sessionToken = await getSessionToken();
        updateStatus('✅ Token obtained');
        
        updateStatus('🔧 Creating client...');
        anamClient = createClient(sessionToken);
        console.log('✅ Client created:', anamClient);
        
        // Add transcript capture event listener with debug display
        anamClient.addListener(AnamEvent.MESSAGE_HISTORY_UPDATED, (messages) => {
            conversationTranscript = messages;
            console.log('📝 Transcript updated:', messages.length, 'messages');
            updateTranscriptDebugBox(messages);
        });
        
        // Add listener for when streaming starts (session ID might be available then)
        anamClient.addListener(AnamEvent.STREAM_STARTED, (event) => {
            console.log('🎬 Stream started, checking for session ID again...');
            console.log('🎬 Stream started event data:', event);
            
            if (!currentSessionId) {
                try {
                    // Try to get from event first
                    if (event && event.sessionId) {
                        currentSessionId = event.sessionId;
                        console.log('📋 Session ID captured from event:', currentSessionId);
                    } else if (anamClient.sessionId) {
                        currentSessionId = anamClient.sessionId;
                        console.log('📋 Session ID captured after stream start:', currentSessionId);
                    } else if (anamClient.session && anamClient.session.id) {
                        currentSessionId = anamClient.session.id;
                        console.log('📋 Session ID captured from session after stream start:', currentSessionId);
                    }
                    
                    // Validate it's a UUID, not "auth"
                    if (currentSessionId && currentSessionId !== 'auth') {
                        console.log('✅ Valid session ID captured:', currentSessionId);
                    } else {
                        console.warn('⚠️ Invalid session ID detected:', currentSessionId);
                        currentSessionId = null;
                    }
                } catch (error) {
                    console.error('❌ Error capturing session ID after stream start:', error);
                }
            }
        });
        
        // Try to capture session ID from client
        try {
            console.log('🔍 Inspecting anamClient for session ID:', anamClient);
            
            // Method 1: Check if session ID is available in client properties
            if (anamClient.sessionId) {
                currentSessionId = anamClient.sessionId;
                console.log('📋 Session ID captured from client.sessionId:', currentSessionId);
            } else if (anamClient.session && anamClient.session.id) {
                currentSessionId = anamClient.session.id;
                console.log('📋 Session ID captured from client.session.id:', currentSessionId);
            } else {
                console.log('⚠️ Session ID not immediately available in client properties');
                console.log('🔍 Available client properties:', Object.keys(anamClient));
                
                // Try to extract from session token (if it contains session info)
                currentSessionId = extractSessionIdFromToken(sessionToken);
                if (currentSessionId) {
                    console.log('📋 Session ID extracted from token:', currentSessionId);
                } else {
                    console.log('⚠️ Could not extract session ID from token either');
                }
            }
        } catch (error) {
            console.error('❌ Error capturing session ID:', error);
        }
        
        updateStatus('📹 Starting stream...');
        
        // Create video element (hidden initially)
        const video = document.createElement('video');
        video.id = 'anam-element-video';
        video.width = 400;
        video.height = 300;
        video.autoplay = true;
        video.playsInline = true;
        video.muted = false;
        video.controls = false;
        video.style.cssText = 'width: 100%; height: auto; border-radius: 12px; background: #000; display: none;';
        
        // Add video to container (hidden)
        customContainer.appendChild(video);
        
        // Stream to video element
        await anamClient.streamToVideoElement('anam-element-video');
        
        console.log('🎬 Streaming to element completed');
        
        // CRITICAL: Capture session ID immediately after streaming starts
        setTimeout(() => {
            if (!currentSessionId || currentSessionId === 'auth') {
                console.log('🔍 POST-STREAM: Attempting to capture session ID...');
                console.log('🔍 POST-STREAM: anamClient keys:', Object.keys(anamClient));
                
                // Try all possible locations
                const possibleId = anamClient.sessionId || 
                                 (anamClient.session && anamClient.session.id) ||
                                 (anamClient._session && anamClient._session.id) ||
                                 (anamClient._sessionId) ||
                                 (anamClient.connection && anamClient.connection.sessionId);
                
                if (possibleId && possibleId !== 'auth' && possibleId.length > 30) {
                    currentSessionId = possibleId;
                    console.log('✅ POST-STREAM: Session ID captured:', currentSessionId);
                } else {
                    console.error('❌ POST-STREAM: Could not find valid session ID');
                    console.log('🔍 POST-STREAM: Full anamClient:', anamClient);
                }
            }
        }, 2000); // Wait 2 seconds for session to be established
        
        // Replace loading with video and controls
        customContainer.innerHTML = '';
        video.style.display = 'block';
        customContainer.appendChild(video);
        
        // Add transcript debug box
        const transcriptDebug = document.createElement('div');
        transcriptDebug.id = 'transcript-debug';
        transcriptDebug.style.cssText = 'margin-top: 15px; padding: 15px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; max-height: 300px; overflow-y: auto;';
        transcriptDebug.innerHTML = '<div style="font-weight: bold; margin-bottom: 10px; color: #666;">📝 Live Transcript</div><div id="transcript-messages" style="font-size: 13px; line-height: 1.6;"><em style="color: #999;">Waiting for conversation to start...</em></div>';
        customContainer.appendChild(transcriptDebug);
        
        // Add expand button
        const expandBtn = document.createElement('button');
        expandBtn.innerHTML = '⛶';
        expandBtn.title = 'Expand to full screen';
        expandBtn.style.cssText = 'position: absolute; top: 8px; left: 8px; width: 24px; height: 24px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 14px; z-index: 10001; display: flex; align-items: center; justify-content: center;';
        expandBtn.addEventListener('click', () => {
            expandElementToModal(customContainer, video);
        });
        customContainer.appendChild(expandBtn);
        
        // Add close button
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.title = 'Close avatar';
        closeBtn.style.cssText = 'position: absolute; top: 8px; right: 8px; width: 24px; height: 24px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 16px; z-index: 10001; display: flex; align-items: center; justify-content: center;';
        closeBtn.addEventListener('click', async () => {
            await closeElementIdAvatar(customContainer);
        });
        customContainer.appendChild(closeBtn);
        
        console.log('✅ Element ID avatar ready');
        
    } catch (error) {
        console.error('❌ Error initializing Element ID avatar:', error);
        customContainer.innerHTML = `
            <div style="padding: 20px; text-align: center; background: #fee; border-radius: 12px; color: #c33;">
                <div style="font-size: 24px; margin-bottom: 10px;">⚠️</div>
                <div style="font-size: 14px; font-weight: bold;">Avatar initialization failed</div>
                <div style="font-size: 12px; margin-top: 5px;">${error.message}</div>
                <button onclick="showElementIdWelcome()" style="margin-top: 15px; padding: 8px 16px; border: none; background: #007cba; color: white; border-radius: 4px; cursor: pointer;">Try Again</button>
            </div>
        `;
    }
}

function expandElementToModal(container, video) {
    console.log('🔄 Expanding element avatar to modal...');
    
    // Store reference to original container
    originalWidget = container;
    
    // Create modal
    const { modal, modalContent } = createModal();
    currentModal = modal;
    
    // Add collapse button to modal (left side)
    const collapseBtn = document.createElement('button');
    collapseBtn.innerHTML = '⤡';
    collapseBtn.title = 'Return to container';
    collapseBtn.style.cssText = 'position: absolute; top: 15px; left: 15px; width: 32px; height: 32px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 18px; z-index: 100001; display: flex; align-items: center; justify-content: center;';
    collapseBtn.addEventListener('click', () => collapseElementToContainer(container, video));
    modalContent.appendChild(collapseBtn);
    
    // Add close button to modal (right side)
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '&times;';
    closeBtn.title = 'Close avatar';
    closeBtn.style.cssText = 'position: absolute; top: 15px; right: 15px; width: 32px; height: 32px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 20px; z-index: 100001; display: flex; align-items: center; justify-content: center;';
    closeBtn.addEventListener('click', async () => {
        await closeElementIdAvatar(container);
    });
    modalContent.appendChild(closeBtn);
    
    // Move video to modal
    video.style.cssText = 'width: 100%; height: 100%; object-fit: cover; border-radius: 12px;';
    modalContent.appendChild(video);
    
    // Hide original container
    container.style.display = 'none';
    
    // Add backdrop click to collapse
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            collapseElementToContainer(container, video);
        }
    });
    
    // Add keyboard support
    const handleKeydown = (e) => {
        if (e.key === 'Escape') {
            collapseElementToContainer(container, video);
            document.removeEventListener('keydown', handleKeydown);
        }
    };
    document.addEventListener('keydown', handleKeydown);
    
    // Add modal to page
    document.body.appendChild(modal);
    
    // Fade in modal
    setTimeout(() => {
        modal.style.opacity = '1';
    }, 10);
    
    console.log('✅ Element avatar expanded to modal');
}

function collapseElementToContainer(container, video) {
    console.log('🔄 Collapsing avatar to container...');
    
    if (!currentModal) {
        console.error('❌ No modal reference found');
        return;
    }
    
    // Restore video styling for container
    video.style.cssText = 'width: 100%; height: auto; border-radius: 12px; background: #000; display: block;';
    
    // Move video back to container
    container.innerHTML = '';
    container.appendChild(video);
    
    // Re-add buttons to container
    const expandBtn = document.createElement('button');
    expandBtn.innerHTML = '⛶';
    expandBtn.title = 'Expand to full screen';
    expandBtn.style.cssText = 'position: absolute; top: 8px; left: 8px; width: 24px; height: 24px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 14px; z-index: 10001; display: flex; align-items: center; justify-content: center;';
    expandBtn.addEventListener('click', () => {
        expandElementToModal(container, video);
    });
    container.appendChild(expandBtn);
    
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '&times;';
    closeBtn.title = 'Close avatar';
    closeBtn.style.cssText = 'position: absolute; top: 8px; right: 8px; width: 24px; height: 24px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 16px; z-index: 10001; display: flex; align-items: center; justify-content: center;';
    closeBtn.addEventListener('click', async () => {
        await closeElementIdAvatar(container);
    });
    container.appendChild(closeBtn);
    
    // Show original container
    container.style.display = 'block';
    
    // Remove modal
    currentModal.style.opacity = '0';
    setTimeout(() => {
        if (currentModal) {
            currentModal.remove();
            currentModal = null;
        }
    }, 300);
    
    console.log('✅ Avatar collapsed to container');
}

async function closeElementIdAvatar(container) {
    console.log('🚫 Closing Element ID avatar completely...');
    
    try {
        // Process session before stopping stream
        // Try to get session ID from multiple sources
        let sessionIdToProcess = currentSessionId || getSessionIdFromClient();
        
        if (!sessionIdToProcess && anamClient) {
            // Try to get from Anam client properties
            console.log('🔍 Searching anamClient for session ID...');
            console.log('🔍 anamClient keys:', Object.keys(anamClient));
            
            // Check various possible locations
            sessionIdToProcess = anamClient.sessionId || 
                               (anamClient.session && anamClient.session.id) ||
                               (anamClient._session && anamClient._session.id) ||
                               (anamClient.connection && anamClient.connection.sessionId);
        }
        
        if (sessionIdToProcess) {
            // Validate session ID is a UUID, not "auth"
            if (sessionIdToProcess === 'auth' || sessionIdToProcess.length < 30) {
                console.error('❌ Invalid session ID detected:', sessionIdToProcess);
                console.log('🔍 Attempting to find real session ID...');
                
                // Try harder to get the real session ID
                if (anamClient && anamClient._session) {
                    sessionIdToProcess = anamClient._session.id || anamClient._session.sessionId;
                    console.log('🔍 Found in _session:', sessionIdToProcess);
                }
            }
            
            // Only proceed if we have a valid UUID
            if (sessionIdToProcess && sessionIdToProcess !== 'auth' && sessionIdToProcess.length > 30) {
                console.log('📋 Processing session:', sessionIdToProcess);
                console.log('💬 Transcript messages:', conversationTranscript.length);
                console.log('📝 Transcript data:', conversationTranscript);
                
                // Save transcript to database
                await saveTranscriptToDatabase(sessionIdToProcess);
                
                currentSessionId = null; // Clear session ID after sending
            } else {
                console.error('❌ Could not find valid session ID. Got:', sessionIdToProcess);
                console.log('🔍 Final debug - anamClient structure:', anamClient);
            }
        } else {
            console.log('⚠️ No session ID available for processing');
            console.log('🔍 Final debug - anamClient structure:', anamClient);
        }
        
        conversationTranscript = []; // Clear transcript data
        
        // Stop streaming if client exists
        if (anamClient) {
            console.log('🛑 Stopping avatar stream...');
            await anamClient.stopStreaming();
            console.log('✅ Stream stopped');
        }
    } catch (error) {
        console.error('❌ Error during close:', error);
    }
    
    // Remove modal if it exists
    if (currentModal) {
        currentModal.remove();
        currentModal = null;
    }
    
    // Reset client
    anamClient = null;
    originalWidget = null;
    
    // Return to welcome screen
    showElementIdWelcome();
    
    console.log('✅ Element ID avatar closed and reset to welcome screen');
}

// Modal and expand/collapse functionality
let currentModal = null;
let originalWidget = null;

function createModal() {
    const modal = document.createElement('div');
    modal.id = 'anam-avatar-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.9);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;
    
    const modalContent = document.createElement('div');
    modalContent.style.cssText = `
        position: relative;
        width: 80vw;
        height: 60vh;
        max-width: 800px;
        max-height: 600px;
        background: #000;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    `;
    
    modal.appendChild(modalContent);
    return { modal, modalContent };
}

function expandToModal() {
    console.log('🔄 Expanding avatar to modal...');
    
    const widget = document.getElementById('anam-avatar-widget');
    const video = widget.querySelector('#anam-default-video');
    
    if (!video) {
        console.error('❌ No video element found to expand');
        return;
    }
    
    // Store reference to original widget
    originalWidget = widget;
    
    // Create modal
    const { modal, modalContent } = createModal();
    currentModal = modal;
    
    // Add collapse button to modal (left side)
    const collapseBtn = document.createElement('button');
    collapseBtn.innerHTML = '⤡';
    collapseBtn.title = 'Return to widget';
    collapseBtn.style.cssText = 'position: absolute; top: 15px; left: 15px; width: 32px; height: 32px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 18px; z-index: 100001; display: flex; align-items: center; justify-content: center;';
    collapseBtn.addEventListener('click', collapseToWidget);
    modalContent.appendChild(collapseBtn);
    
    // Add close button to modal (right side)
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '&times;';
    closeBtn.title = 'Close avatar';
    closeBtn.style.cssText = 'position: absolute; top: 15px; right: 15px; width: 32px; height: 32px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 20px; z-index: 100001; display: flex; align-items: center; justify-content: center;';
    closeBtn.addEventListener('click', async () => {
        await closeAvatarCompletely();
    });
    modalContent.appendChild(closeBtn);
    
    // Move video to modal
    video.style.cssText = 'width: 100%; height: 100%; object-fit: cover; border-radius: 12px;';
    modalContent.appendChild(video);
    
    // Hide original widget
    widget.style.display = 'none';
    
    // Add backdrop click to collapse
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            collapseToWidget();
        }
    });
    
    // Add keyboard support
    const handleKeydown = (e) => {
        if (e.key === 'Escape') {
            collapseToWidget();
            document.removeEventListener('keydown', handleKeydown);
        }
    };
    document.addEventListener('keydown', handleKeydown);
    
    // Add modal to page
    document.body.appendChild(modal);
    
    // Fade in modal
    setTimeout(() => {
        modal.style.opacity = '1';
    }, 10);
    
    console.log('✅ Avatar expanded to modal');
}

function collapseToWidget() {
    console.log('🔄 Collapsing avatar to widget...');
    
    if (!currentModal || !originalWidget) {
        console.error('❌ No modal or widget reference found');
        return;
    }
    
    const video = currentModal.querySelector('#anam-default-video');
    
    if (!video) {
        console.error('❌ No video element found in modal');
        return;
    }
    
    // Restore video styling for widget
    video.style.cssText = 'width: 100%; height: auto; border-radius: 10px; background: #000; display: block;';
    
    // Move video back to widget
    originalWidget.appendChild(video);
    
    // Show original widget
    originalWidget.style.display = 'block';
    
    // Remove modal
    currentModal.style.opacity = '0';
    setTimeout(() => {
        if (currentModal) {
            currentModal.remove();
            currentModal = null;
        }
    }, 300);
    
    console.log('✅ Avatar collapsed to widget');
}

async function closeAvatarCompletely() {
    console.log('🚫 Closing avatar completely...');
    
    try {
        // Process session before stopping stream
        let sessionIdToProcess = currentSessionId || getSessionIdFromClient();
        
        if (!sessionIdToProcess && anamClient) {
            // Try to get from Anam client properties
            console.log('🔍 Searching anamClient for session ID...');
            sessionIdToProcess = anamClient.sessionId || 
                               (anamClient.session && anamClient.session.id) ||
                               (anamClient._session && anamClient._session.id) ||
                               (anamClient.connection && anamClient.connection.sessionId);
        }
        
        if (sessionIdToProcess) {
            console.log('📋 Processing session:', sessionIdToProcess);
            
            // Store session ID on server for audit trail
            await sendSessionIdToServer(sessionIdToProcess);
            
            currentSessionId = null; // Clear session ID after sending
        } else {
            console.log('⚠️ No session ID available for processing');
        }
        
        conversationTranscript = []; // Clear transcript data
        
        // Stop streaming if client exists
        if (anamClient) {
            console.log('🛑 Stopping avatar stream...');
            await anamClient.stopStreaming();
            console.log('✅ Stream stopped');
        }
    } catch (error) {
        console.error('❌ Error during close:', error);
    }
    
    // Remove modal if it exists
    if (currentModal) {
        currentModal.remove();
        currentModal = null;
    }
    
    // Reset to welcome screen
    const widget = document.getElementById('anam-avatar-widget');
    if (widget) {
        // Restore welcome screen content
        widget.innerHTML = `
            <div style="padding: 20px; text-align: center; position: relative;">
                <div style="font-size: 32px; margin-bottom: 15px;">💬</div>
                <h3 style="margin: 0 0 10px 0; color: #333; font-size: 16px;">Ready to chat?</h3>
                <p style="margin: 0 0 20px 0; color: #666; font-size: 14px; line-height: 1.4;">
                    Start a conversation with our AI assistant
                </p>
                <button id="anam-start-btn" style="padding: 12px 24px; border: none; background: #007cba; color: white; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: bold;">
                    Start Conversation
                </button>
            </div>
        `;
        
        // Reset widget styling
        widget.style.padding = '';
        widget.style.width = '300px';
        widget.style.height = 'auto';
        widget.style.display = 'block';
        
        // Re-attach event listeners
        const newStartBtn = document.getElementById('anam-start-btn');
        
        newStartBtn.addEventListener('click', () => {
            initAvatar();
        });
    }
    
    // Reset client
    anamClient = null;
    originalWidget = null;
    
    console.log('✅ Avatar closed and reset to welcome screen');
}

async function getSessionToken() {
    updateStatus('🔑 Getting session token...');
    
    try {
        const formData = new FormData();
        formData.append('action', 'anam_get_session_token');
        formData.append('nonce', ANAM_CONFIG.nonce);
        
        const response = await fetch(ANAM_CONFIG.ajaxUrl, {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.data && data.data.sessionToken) {
            return data.data.sessionToken;
        } else {
            throw new Error(data.data || 'No session token in response');
        }
    } catch (error) {
        console.error('❌ Session token error:', error);
        throw error;
    }
}

async function initAvatar() {
    try {
        updateStatus('🚀 Initializing...');
        
        const sessionToken = await getSessionToken();
        updateStatus('✅ Token obtained');
        
        updateStatus('🔧 Creating client...');
        anamClient = createClient(sessionToken);
        console.log('✅ Client created:', anamClient);
        
        // Simple session ID capture - no real-time transcript needed
        anamClient.addListener(AnamEvent.STREAM_STARTED, () => {
            console.log('🎬 Stream started');
            if (anamClient.sessionId) {
                currentSessionId = anamClient.sessionId;
                console.log('📋 Session ID captured:', currentSessionId);
            }
        });
        
        // Try to capture session ID from client
        try {
            console.log('🔍 Inspecting anamClient for session ID:', anamClient);
            
            // Method 1: Check if session ID is available in client properties
            if (anamClient.sessionId) {
                currentSessionId = anamClient.sessionId;
                console.log('📋 Session ID captured from client.sessionId:', currentSessionId);
            } else if (anamClient.session && anamClient.session.id) {
                currentSessionId = anamClient.session.id;
                console.log('📋 Session ID captured from client.session.id:', currentSessionId);
            } else {
                console.log('⚠️ Session ID not immediately available in client properties');
                console.log('🔍 Available client properties:', Object.keys(anamClient));
                
                // Try to extract from session token (if it contains session info)
                currentSessionId = extractSessionIdFromToken(sessionToken);
                if (currentSessionId) {
                    console.log('📋 Session ID extracted from token:', currentSessionId);
                } else {
                    console.log('⚠️ Could not extract session ID from token either');
                }
            }
        } catch (error) {
            console.error('❌ Error capturing session ID:', error);
        }
        
        updateStatus('📹 Starting stream...');
        
        // Handle different display methods
        if (ANAM_CONFIG.displayMethod === 'page_position') {
            // Page Position method - replace widget content with video
            const widget = document.getElementById('anam-avatar-widget');
            
            const video = document.createElement('video');
            video.id = 'anam-default-video';
            video.width = 300;
            video.height = 400;
            video.autoplay = true; // Need autoplay for streaming to work
            video.playsInline = true;
            video.muted = false;
            video.controls = false;
            video.style.cssText = 'width: 100%; height: auto; border-radius: 10px; background: #000; display: block;';
            
            // Show loading state first
            widget.innerHTML = `
                <div id="anam-loading" style="padding: 40px 20px; text-align: center; background: #f8f9fa;">
                    <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #e3e3e3; border-top: 4px solid #007cba; border-radius: 50%; animation: anam-spin 1s linear infinite; margin-bottom: 15px;"></div>
                    <div style="color: #666; font-size: 14px;">Setting up your avatar...</div>
                    <div style="color: #999; font-size: 12px; margin-top: 5px;">This may take a moment</div>
                </div>
                <style>
                    @keyframes anam-spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                </style>
            `;
            
            // Maintain the widget's fixed positioning and size
            widget.style.position = 'fixed';
            widget.style.width = '300px';
            widget.style.height = 'auto';
            widget.style.padding = '0';
            
            console.log('🎥 Video element created and loading screen shown');
            
            // Add video element to DOM first (hidden behind loading screen)
            video.style.display = 'none';
            widget.appendChild(video);
            
            await anamClient.streamToVideoElement('anam-default-video');
            
            console.log('🎬 Streaming to video element completed');
            
            // Replace loading screen with video and controls
            widget.innerHTML = '';
            widget.style.padding = '0';
            video.style.display = 'block';
            widget.appendChild(video);
            
            // Add expand button
            const expandBtn = document.createElement('button');
            expandBtn.innerHTML = '⛶';
            expandBtn.title = 'Expand to full screen';
            expandBtn.style.cssText = 'position: absolute; top: 8px; left: 8px; width: 24px; height: 24px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 14px; z-index: 10001; display: flex; align-items: center; justify-content: center;';
            expandBtn.addEventListener('click', () => {
                expandToModal();
            });
            widget.appendChild(expandBtn);
            
            // Add close button
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '&times;';
            closeBtn.title = 'Close avatar';
            closeBtn.style.cssText = 'position: absolute; top: 8px; right: 8px; width: 24px; height: 24px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; cursor: pointer; font-size: 16px; z-index: 10001; display: flex; align-items: center; justify-content: center;';
            closeBtn.addEventListener('click', async () => {
                await closeAvatarCompletely();
            });
            widget.appendChild(closeBtn);
            
        } else {
            throw new Error('Invalid display method: ' + ANAM_CONFIG.displayMethod);
        }
        console.log('✅ Stream started');
        
        updateStatus('🎉 Avatar ready!');
        
        setTimeout(() => {
            const status = document.getElementById('anam-avatar-status');
            if (status) status.style.display = 'none';
        }, 3000);
        
    } catch (error) {
        console.error('❌ Initialization failed:', error);
        updateStatus(`❌ Failed: ${error.message}`, true);
    }
}

function getPositionStyles(position) {
    switch(position) {
        case 'top-left': return 'top: 20px; left: 20px';
        case 'top-right': return 'top: 20px; right: 20px';
        case 'bottom-left': return 'bottom: 20px; left: 20px';
        case 'bottom-right': 
        default: return 'bottom: 20px; right: 20px';
    }
}

window.anamAvatar = {
    client: () => anamClient,
    reinit: initAvatar,
    config: ANAM_CONFIG
};
