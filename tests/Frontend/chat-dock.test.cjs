const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

for (const widgetFile of ['widget.js', 'live-widget.js']) {
    test(`${widgetFile} persists visitor identity between visits`, () => {
        const widgetPath = path.join(__dirname, '..', '..', 'public', 'widget', widgetFile);
        const widget = fs.readFileSync(widgetPath, 'utf8');
        const start = widget.indexOf('let SESSION_ID =');
        const end = widget.indexOf('\n\n    const PUSHER_KEY', start);
        assert.notEqual(start, -1, 'visitor session initialization must exist');
        assert.notEqual(end, -1, 'visitor session initialization must precede widget configuration');

        const localValues = new Map();
        const sessionValues = new Map([['chat_session_id', 'existing-visitor']]);
        const context = {
            localStorage: {
                getItem: key => localValues.get(key) || null,
                setItem: (key, value) => localValues.set(key, value)
            },
            sessionStorage: {
                getItem: key => sessionValues.get(key) || null,
                setItem: (key, value) => sessionValues.set(key, value)
            }
        };

        vm.runInNewContext(widget.slice(start, end), context);

        assert.equal(localValues.get('chat_session_id'), 'existing-visitor');
        assert.equal(sessionValues.get('chat_session_id'), 'existing-visitor');
    });

    test(`${widgetFile} prefers the persisted visitor identity on later visits`, () => {
        const widgetPath = path.join(__dirname, '..', '..', 'public', 'widget', widgetFile);
        const widget = fs.readFileSync(widgetPath, 'utf8');
        const start = widget.indexOf('let SESSION_ID =');
        const end = widget.indexOf('\n\n    const PUSHER_KEY', start);
        const localValues = new Map([['chat_session_id', 'persistent-visitor']]);
        const sessionValues = new Map([['chat_session_id', 'stale-tab-visitor']]);
        const context = {
            localStorage: {
                getItem: key => localValues.get(key) || null,
                setItem: (key, value) => localValues.set(key, value)
            },
            sessionStorage: {
                getItem: key => sessionValues.get(key) || null,
                setItem: (key, value) => sessionValues.set(key, value)
            }
        };

        vm.runInNewContext(widget.slice(start, end), context);

        assert.equal(sessionValues.get('chat_session_id'), 'persistent-visitor');
    });
}

test('both visitor widgets send referrer and landing URL for attribution', () => {
    for (const widgetFile of ['widget.js', 'live-widget.js']) {
        const widgetPath = path.join(__dirname, '..', '..', 'public', 'widget', widgetFile);
        const widget = fs.readFileSync(widgetPath, 'utf8');
        const initStart = widget.indexOf('fetch(`${BASE_URL}/api/visitor-init`');
        const initEnd = widget.indexOf('.then((res) => res.json())', initStart);
        const initRequest = widget.slice(initStart, initEnd);

        assert.notEqual(initStart, -1, `${widgetFile} visitor init request must exist`);
        assert.match(initRequest, /referrer:\s*document\.referrer\s*\|\|\s*null/);
        assert.match(initRequest, /landing_url:\s*window\.location\.href/);
    }
});

test('both visitor widgets use the copied script URL as their API base URL', () => {
    for (const widgetFile of ['widget.js', 'live-widget.js']) {
        const widgetPath = path.join(__dirname, '..', '..', 'public', 'widget', widgetFile);
        const widget = fs.readFileSync(widgetPath, 'utf8');
        const baseUrlStart = widget.indexOf('const BASE_URL = ');
        const baseUrlEnd = widget.indexOf(';', baseUrlStart);
        assert.notEqual(baseUrlStart, -1, `${widgetFile} must define its API base from the embed URL`);

        const expression = widget.slice(baseUrlStart, baseUrlEnd + 1);
        const baseUrlFor = scriptUrl => vm.runInNewContext(`${expression}\nBASE_URL`, {
            URL,
            document: { currentScript: { src: scriptUrl } }
        });
        assert.equal(baseUrlFor('http://localhost/live-chat/public/widget/widget.js?brand=4'), 'http://localhost/live-chat/public');
        assert.equal(baseUrlFor('https://chat.example.com/live-chat/public/widget/widget.js?brand=4'), 'https://chat.example.com/live-chat/public');
    }
});

test('visitor profile avatars use country flags when a country code exists', () => {
    const chatView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat.blade.php'), 'utf8');
    const contactsView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat', '_contacts.blade.php'), 'utf8');
    const messagesView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat', 'messages.blade.php'), 'utf8');
    const layoutView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'layout', 'app.blade.php'), 'utf8');

    assert.match(contactsView, /data-country-code="{{ \$chat->main_visitor->country_code/);
    assert.match(contactsView, /https:\/\/flagcdn\.com\/w40\/\{\{ strtolower\(\$chat->main_visitor->country_code\) \}\}\.png/);
    assert.match(chatView, /id="sidebar-avatar-flag"/);
    assert.match(chatView, /flag\.src = `https:\/\/flagcdn\.com\/w40\/\$\{countryCode\}\.png`/);
    assert.match(chatView, /function renderMessage\([\s\S]*?countryCode = null\)/);
    assert.match(chatView, /https:\/\/flagcdn\.com\/w40\/\$\{countryCode\}\.png/);
    assert.match(chatView, /msg\.user\?\.country_code/);
    assert.match(messagesView, /https:\/\/flagcdn\.com\/w40\/\{\{ strtolower\(\$chat->main_visitor->country_code\) \}\}\.png/);
    assert.match(layoutView, /message\.user\?\.country_code/);
});

test('chat inbox has separate online and offline history views with recurring visitor heartbeats', () => {
    const chatView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat.blade.php'), 'utf8');
    const contactsView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat', '_contacts.blade.php'), 'utf8');
    const sidebarView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'layout', 'sidebar.blade.php'), 'utf8');
    const controller = fs.readFileSync(path.join(__dirname, '..', '..', 'app', 'Http', 'Controllers', 'AdminController.php'), 'utf8');
    const widget = fs.readFileSync(path.join(__dirname, '..', '..', 'public', 'widget', 'widget.js'), 'utf8');
    const liveWidget = fs.readFileSync(path.join(__dirname, '..', '..', 'public', 'widget', 'live-widget.js'), 'utf8');

    assert.match(controller, /historyView = request\(\)->query\('view'\) === 'history'/);
    assert.match(controller, /now\(\)->subMinutes\(3\)/);
    assert.match(contactsView, /No offline visitors found/);
    assert.match(chatView, /setInterval\(refreshChatContacts, 30000\)/);
    assert.match(sidebarView, /route\('admin\.chat', \['view' => 'history'\]\)/);
    assert.match(widget, /setInterval\(sendHeartbeat, 30000\)/);
    assert.match(liveWidget, /setInterval\(sendHeartbeat, 30000\)/);
});

test('live visitor presence events refresh the chat list without a page reload', () => {
    const chatView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat.blade.php'), 'utf8');
    const visitorController = fs.readFileSync(path.join(__dirname, '..', '..', 'app', 'Http', 'Controllers', 'Api', 'VisitorController.php'), 'utf8');

    assert.match(visitorController, /'visitor-online'/);
    assert.match(chatView, /bind\('visitor-online'/);
    assert.match(chatView, /refreshChatContacts\(\)/);
});

test('visitor notifications render country flags when the visitor country code is available', () => {
    const headerView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'layout', 'header.blade.php'), 'utf8');
    const appView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'layout', 'app.blade.php'), 'utf8');
    const visitorController = fs.readFileSync(path.join(__dirname, '..', '..', 'app', 'Http', 'Controllers', 'Api', 'VisitorController.php'), 'utf8');
    const notification = fs.readFileSync(path.join(__dirname, '..', '..', 'app', 'Notifications', 'NewVisitorNotification.php'), 'utf8');

    assert.match(headerView, /notification->data\['country_code'\]/);
    assert.match(headerView, /flagcdn\.com\/w40\/\{\{ strtolower\(\$notification->data\['country_code'\]\) \}\}\.png/);
    assert.match(appView, /visitorCountryCode/);
    assert.match(appView, /flagcdn\.com\/w40\/\$\{visitorCountryCode\}\.png/);
    assert.match(visitorController, /'country_code' => \$visitor->country_code/);
    assert.match(notification, /'country_code' => \$this->visitor->country_code/);
});

test('agent chat composer only shows saved shortcuts for slash suggestions', () => {
    const partialPath = path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat', 'messages.blade.php');
    const layoutPath = path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'layout', 'app.blade.php');
    const partial = fs.readFileSync(partialPath, 'utf8');
    const layout = fs.readFileSync(layoutPath, 'utf8');

    const sidebarPath = path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'layout', 'sidebar.blade.php');
    const managerPath = path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat-shortcuts.blade.php');
    const sidebar = fs.readFileSync(sidebarPath, 'utf8');
    const manager = fs.readFileSync(managerPath, 'utf8');

    assert.doesNotMatch(partial, /chat-shortcut-toggle|chat-shortcut-create-form|chat-shortcut-search/);
    assert.match(partial, /class="chat-shortcut-list"/);
    assert.match(sidebar, /route\('admin\.chat-shortcuts\.index'\)/);
    assert.match(manager, /id="shortcut-new"/);
    assert.match(manager, /shortcut-edit/);
    assert.match(manager, /shortcut-delete/);
    assert.match(manager, /name="scope"/);
    assert.match(manager, /name="brand_id"/);
    assert.match(layout, /function getSlashShortcut\(input\)/);
    assert.match(layout, /loadChatShortcuts\(panel, chatId, slash\.query\)/);
    assert.doesNotMatch(layout, /chat-shortcut-manager|chatShortcutUpdateRoute/);
    assert.match(layout, /firstOption\.click\(\)/);
    assert.match(layout, /function applyChatShortcut\(input, shortcut\)/);
});

test('shortcut manager script parses as valid JavaScript', () => {
    const layoutPath = path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'layout', 'app.blade.php');
    const layout = fs.readFileSync(layoutPath, 'utf8');
    const start = layout.indexOf('<script>\n        const chatShortcutRoute');
    const end = layout.indexOf('</script>', start);
    assert.notEqual(start, -1, 'shortcut manager script must exist');
    const script = layout.slice(start + '<script>'.length, end)
        .replace(/@json\(route\('[^']+',\s*\[[^\]]+\]\)\)/g, '"/admin/chat/:chatId/shortcuts/:shortcutId"')
        .replace(/@json\(route\('[^']+',\s*'[^']+'\)\)/g, '"/admin/chat/:id/shortcuts"');

    assert.doesNotThrow(() => new vm.Script(script));
});

test('typing slash command replaces it with the selected shortcut reply', () => {
    const layoutPath = path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'layout', 'app.blade.php');
    const layout = fs.readFileSync(layoutPath, 'utf8');
    const start = layout.indexOf('function getSlashShortcut(input) {');
    const end = layout.indexOf('\n        async function loadChatShortcuts', start);
    assert.notEqual(start, -1, 'slash shortcut parser must exist');
    assert.notEqual(end, -1, 'shortcut insertion function must be defined');

    const input = {
        value: 'Hi /greet',
        selectionStart: 9,
        selectionEnd: 9,
        focus() {},
        setSelectionRange(start, end) {
            this.selectionStart = start;
            this.selectionEnd = end;
        },
        dispatchEvent() {}
    };
    const context = {
        Event,
        getSlashShortcut: undefined
    };
    vm.runInNewContext(
        `${layout.slice(start, end)}; globalThis.result = getSlashShortcut(input); applyChatShortcut(input, { content: 'Hello there!' });`,
        { ...context, input }
    );

    assert.equal(input.value, 'Hi Hello there!');
    assert.equal(input.selectionStart, 15);
    assert.equal(input.selectionEnd, 15);
});

test('past-chat navigation does not create a current chat when none exists', () => {
    const chatViewPath = path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat.blade.php');
    const chatView = fs.readFileSync(chatViewPath, 'utf8');
    const start = chatView.indexOf('function renderPastChats(chats, currentChatId, pastMessageCount) {');
    const end = chatView.indexOf('\nfunction updateCurrentInboxChat', start);
    assert.notEqual(start, -1, 'past chat renderer must exist');
    assert.notEqual(end, -1, 'past chat renderer must precede profile loading');

    const createElement = () => ({
        children: [],
        dataset: {},
        classList: { remove() {} },
        replaceChildren() {
            this.children = [];
        },
        appendChild(child) {
            this.children.push(child);
        },
        addEventListener(eventName, handler) {
            this.handlers = { ...this.handlers, [eventName]: handler };
        },
        setAttribute(name, value) {
            this.attributes = { ...this.attributes, [name]: value };
        }
    });
    const elements = new Map([
        ['past-chat-list', createElement()],
        ['profile-past-chat-count', createElement()],
        ['past-chat-count', createElement()]
    ]);
    let selectedChat = null;
    const context = {
        document: {
            getElementById: id => elements.get(id),
            createElement
        },
        openChatTabs: new Map(),
        getChatContact: () => null,
        currentInboxChatId: null,
        activateChatTab(...args) {
            selectedChat = args;
        }
    };

    vm.runInNewContext(
        `${chatView.slice(start, end)}renderPastChats([{ id: 9, status: 'closed', date: '2026-02-17T04:45:08Z', preview: 'Earlier chat' }, { id: 10, status: 'closed', date: '2026-02-16T04:45:08Z', preview: 'Another old chat' }], null, 5);`,
        context
    );
    const pastList = elements.get('past-chat-list');
    pastList.children[0].handlers.click();

    assert.deepEqual(selectedChat, [9, 'past', null, true]);
    assert.equal(context.currentInboxChatId, null);
    assert.equal(pastList.children[0].textContent, 'Earlier chat');
    assert.doesNotMatch(pastList.children[0].textContent, /2026|2\/17/);
    assert.equal(pastList.children.length, 2);
    assert.equal(elements.get('past-chat-count').textContent, '2');
    assert.equal(elements.get('profile-past-chat-count').textContent, '5');
});

test('past-chat tab opens the newest past conversation directly', () => {
    const chatView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat.blade.php'), 'utf8');
    const start = chatView.indexOf('function openLatestPastChat() {');
    const end = chatView.indexOf('\nfunction getChatContact', start);
    assert.notEqual(start, -1, 'past-chat tab must open a past conversation directly');
    assert.notEqual(end, -1, 'direct past-chat opener must precede chat contact lookup');

    let openedChat = null;
    let popupToggled = false;
    const firstPastChat = { click() { openedChat = 9; } };
    const context = {
        document: {
            getElementById(id) {
                assert.equal(id, 'past-chat-list');
                return {
                    querySelector(selector) {
                        assert.equal(selector, 'button');
                        return firstPastChat;
                    },
                    classList: { toggle() { popupToggled = true; } }
                };
            }
        }
    };

    vm.runInNewContext(`${chatView.slice(start, end)}openLatestPastChat();`, context);

    assert.equal(openedChat, 9);
    assert.equal(popupToggled, false);
});

test('loading a past profile preserves the same visitor current chat and clears it for another visitor', () => {
    const chatView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat.blade.php'), 'utf8');
    const start = chatView.indexOf('function updateCurrentInboxChat(profile) {');
    const end = chatView.indexOf('\nasync function loadVisitorProfile', start);
    assert.notEqual(start, -1, 'current chat profile updater must exist');

    const currentTab = { disabled: false };
    const context = {
        currentInboxVisitorId: 'visitor-1',
        currentInboxChatId: 'chat-2',
        document: { getElementById: () => currentTab }
    };
    vm.runInNewContext(
        `${chatView.slice(start, end)}updateCurrentInboxChat({ visitor: { id: 'visitor-1' }, current_chat: null });`,
        context
    );
    assert.equal(context.currentInboxChatId, 'chat-2');
    assert.equal(currentTab.disabled, false);

    vm.runInNewContext(
        `updateCurrentInboxChat({ visitor: { id: 'visitor-2' }, current_chat: null });`,
        context
    );
    assert.equal(context.currentInboxChatId, null);
    assert.equal(currentTab.disabled, true);

    vm.runInNewContext(
        `updateCurrentInboxChat({ visitor: { id: 'visitor-2' }, current_chat: { id: 'chat-9' } });`,
        context
    );
    assert.equal(context.currentInboxChatId, 'chat-9');
    assert.equal(currentTab.disabled, false);
});

test('past message total is displayed in the visitor activity card', () => {
    const chatView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat.blade.php'), 'utf8');
    const profileStart = chatView.indexOf('function renderPastChats(chats, currentChatId, pastMessageCount) {');
    const profileEnd = chatView.indexOf('\nfunction updateCurrentInboxChat', profileStart);
    const updaterStart = profileEnd + '\n'.length;
    const updaterEnd = chatView.indexOf('\nasync function loadVisitorProfile', updaterStart);
    assert.notEqual(profileStart, -1);
    assert.notEqual(updaterStart, -1);

    assert.match(chatView, /<h6[^>]*>Past chats<\/h6>\s*<div class="chat-profile-stat">\s*<strong id="profile-past-chat-count">0<\/strong><span>Past messages<\/span>/);
    assert.match(chatView, /Past chats <span class="badge bg-label-secondary ms-1" id="past-chat-count">0<\/span>/);
    assert.match(chatView, /renderPastChats\(profile\.past_chats, currentInboxChatId, profile\.past_message_count\)/);
    assert.match(chatView, /profile-past-chat-count'\)\.textContent = String\(Math\.max\(0, Number\(pastMessageCount\) \|\| 0\)\)/);
    assert.doesNotMatch(chatView, /id="profile-past-chats"/);
    assert.match(chatView, /document\.getElementById\('past-chat-toggle'\)\.addEventListener\('click', \(\) => \{\s*openLatestPastChat\(\);/);
});

test('selecting a past chat opens its read-only full-history view', () => {
    const chatView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat.blade.php'), 'utf8');
    const messagesView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat', 'messages.blade.php'), 'utf8');

    assert.match(chatView, /if \(isReadOnlyHistory\) getChat \+= '\?past=1'/);
    assert.match(chatView, /if \(!isReadOnlyHistory\) \{\s*subscribeToChat\(id\);\s*markMessagesRead\(id\);/);
    assert.match(chatView, /if \(isPastHistory\) messageParams\.set\('past', '1'\)/);
    assert.match(chatView, /dataset\.readOnlyHistory === 'true'/);
    assert.match(chatView, /if \(!isPastChat\) \{\s*currentInboxChatId = chatStatus === 'closed' \? null : id;/);
    assert.match(chatView, /if \(isPastChat\) \{\s*document\.querySelector\('\.chat-history-footer'\)\?\.remove\(\);/);
    assert.match(chatView, /updateCurrentInboxChat\(profile\)/);
    assert.match(chatView, /if \(!currentInboxChatId\) return;[\s\S]*?activateChatTab\(currentInboxChatId,/);
    assert.match(chatView, /item\.dataset\.isPastChat === 'true'/);
    assert.match(messagesView, /&& !\$readOnly/);
    assert.match(messagesView, /id="load-more-btn"/);
    const contactsView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat', '_contacts.blade.php'), 'utf8');
    assert.match(contactsView, /data-is-past-chat="{{ \$isPastChat \? 'true' : 'false' }}"/);
    assert.match(contactsView, /\$isPastChat \? '\?past=1' : ''/);
    assert.match(contactsView, /\$chat->isPastConversation\(now\(\)->subWeek\(\)\)/);
});

test('past chat count is based on all one-week-old conversations and flags flow into visitor message avatars', () => {
    const controller = fs.readFileSync(path.join(__dirname, '..', '..', 'app', 'Http', 'Controllers', 'AdminController.php'), 'utf8');
    const chatView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat.blade.php'), 'utf8');
    const appView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'layout', 'app.blade.php'), 'utf8');

    assert.match(controller, /'past_chats' => \$pastChats/);
    assert.match(chatView, /document\.getElementById\('past-chat-count'\)\.textContent = String\(pastChats\.length\)/);
    assert.match(chatView, /const avatar = role == 3 && \/\^\[a-z\]\{2\}\$\/\.test\(countryCode\)/);
    assert.match(appView, /avatar\.src = `https:\/\/flagcdn\.com\/w40\/\$\{visitorCountryCode\}\.png`/);
    assert.match(appView, /showChatToast\([\s\S]*?getVisitorCountryCode\(data\)/);
});

test('visitor notification flag lookup accepts country codes from supported event payloads', () => {
    const appView = fs.readFileSync(path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'layout', 'app.blade.php'), 'utf8');
    const start = appView.indexOf('function getVisitorCountryCode(data) {');
    const end = appView.indexOf('\n            function showChatToast', start);
    assert.notEqual(start, -1, 'visitor country-code resolver must exist');
    assert.notEqual(end, -1, 'visitor country-code resolver must end before toast renderer');

    const context = {};
    vm.runInNewContext(`${appView.slice(start, end)}\nthis.resolveCountryCode = getVisitorCountryCode;`, context);

    assert.equal(context.resolveCountryCode({ visitor: { country_code: 'US' } }), 'us');
    assert.equal(context.resolveCountryCode({ country_code: 'GB' }), 'gb');
    assert.equal(context.resolveCountryCode({ message: { user: { country_code: 'CA' } } }), 'ca');
    assert.equal(context.resolveCountryCode({ visitor: { country_code: 'unknown' } }), null);
});

test('visitor profile stays visible on small screens and follows selected chats', () => {
    const chatViewPath = path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat.blade.php');
    const chatView = fs.readFileSync(chatViewPath, 'utf8');
    const mobileBreakpoint = chatView.indexOf('@media (max-width: 767.98px)');
    const mobileStyles = chatView.slice(mobileBreakpoint, chatView.indexOf('\n    }', mobileBreakpoint) + 6);
    const desktopProfileStyles = chatView.slice(
        chatView.indexOf('.zendesk-chat-layout > .row > .app-chat-sidebar-right {'),
        chatView.indexOf('.zendesk-chat-layout .app-chat-sidebar-right .sidebar-header')
    );
    const activateStart = chatView.indexOf('function activateChatTab(');
    const activateEnd = chatView.indexOf('\nfunction closeChatTab', activateStart);
    const activateChat = chatView.slice(activateStart, activateEnd);

    assert.notEqual(mobileBreakpoint, -1, 'mobile layout breakpoint must exist');
    assert.match(desktopProfileStyles, /display:\s*flex\s*!important/);
    assert.match(desktopProfileStyles, /visibility:\s*visible\s*!important/);
    assert.match(desktopProfileStyles, /opacity:\s*1\s*!important/);
    assert.match(desktopProfileStyles, /pointer-events:\s*auto/);
    assert.match(mobileStyles, /\.app-chat-sidebar-right\s*\{[\s\S]*?display:\s*flex\s*!important/);
    assert.match(mobileStyles, /flex-direction:\s*column/);
    assert.match(activateChat, /updateSidebar\(contact\)/);
    assert.match(activateChat, /loadVisitorProfile\(id\)/);
    assert.ok(
        activateChat.indexOf('updateSidebar(contact)') < activateChat.indexOf('loadVisitorProfile(id)'),
        'selected visitor details should update before waiting for the profile request'
    );
});

test('visitor details appear above the profile form and omit address and tags', () => {
    const chatViewPath = path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat.blade.php');
    const chatView = fs.readFileSync(chatViewPath, 'utf8');
    const visitorPathSection = chatView.indexOf('Visitor path');
    const pastChatsSection = chatView.indexOf('Past chats', visitorPathSection);
    const locationSection = chatView.indexOf('Location & device', pastChatsSection);
    const formStart = chatView.indexOf('<form id="visitor-profile-form"');
    const formEnd = chatView.indexOf('</form>', formStart);
    const profileForm = chatView.slice(formStart, formEnd);

    assert.notEqual(visitorPathSection, -1);
    assert.ok(visitorPathSection < pastChatsSection, 'visitor path should be shown above past chats');
    assert.ok(pastChatsSection < locationSection, 'location and device details should follow past chats');
    assert.ok(locationSection < formStart, 'visitor details should appear above the profile form');
    assert.doesNotMatch(profileForm, /profile-address|profile-tags|Address|Tags/);
});

test('visitor navigation system messages display the actual safe URL', () => {
    const chatViewPath = path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat.blade.php');
    const chatView = fs.readFileSync(chatViewPath, 'utf8');
    const start = chatView.indexOf('function renderSystemMessage(text) {');
    const end = chatView.indexOf('\nfunction appendMessage', start);
    assert.notEqual(start, -1, 'system message renderer must exist');

    const nodes = [];
    const createElement = tagName => {
        const element = {
            tagName,
            children: [],
            appendChild(child) {
                this.children.push(child);
            },
            append(child) {
                this.children.push(child);
            }
        };
        nodes.push(element);
        return element;
    };
    const context = {
        document: {
            createElement,
            createTextNode: text => ({ textContent: text })
        },
        URL
    };

    vm.runInNewContext(
        `${chatView.slice(start, end)}renderSystemMessage('Visitor navigated to: https://example.test/pricing?plan=pro');`,
        context
    );

    const link = nodes.find(node => node.tagName === 'a');
    assert.ok(link, 'navigation URL should be a clickable link');
    assert.equal(link.textContent, 'https://example.test/pricing?plan=pro');
    assert.equal(link.href, 'https://example.test/pricing?plan=pro');
    assert.equal(link.rel, 'noopener noreferrer');
    assert.equal(nodes.some(node => node.textContent === 'Site'), false);
});

test('message composer stays inside chat column and picker is clamped away from visitor info', () => {
    const chatViewPath = path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat.blade.php');
    const chatView = fs.readFileSync(chatViewPath, 'utf8');
    const composerStyles = chatView.slice(
        chatView.indexOf('.chat-main-panel .chat-history-footer {'),
        chatView.indexOf('.chat-history-body {', chatView.indexOf('.chat-main-panel .chat-history-footer {'))
    );
    const pickerStart = chatView.indexOf('function positionEmojiPicker(button) {');
    const pickerEnd = chatView.indexOf('\n    picker.on(', pickerStart);
    const pickerPositioner = chatView.slice(pickerStart, pickerEnd);
    const panel = {
        getBoundingClientRect: () => ({ left: 100, right: 700, top: 80, bottom: 680, width: 600, height: 600 })
    };
    const picker = {
        style: {},
        getBoundingClientRect: () => ({ width: 320, height: 400 })
    };
    const context = {
        document: {
            querySelector(selector) {
                if (selector === '.chat-main-panel') return panel;
                if (selector === '.emoji-picker__wrapper') return picker;
                return null;
            }
        },
        Math
    };

    assert.match(composerStyles, /box-sizing:\s*border-box/);
    assert.match(composerStyles, /margin:\s*0/);
    assert.match(composerStyles, /justify-content:\s*flex-start\s*!important/);
    assert.match(composerStyles, /\.message-input\s*\{[\s\S]*?min-width:\s*0/);
    assert.match(composerStyles, /\.message-actions\s*\{[\s\S]*?margin-right:\s*0\.5rem/);
    vm.runInNewContext(`${pickerPositioner}\npositionEmojiPicker({ getBoundingClientRect: () => ({ left: 650, top: 620 }) });`, context);

    assert.equal(picker.style.position, 'fixed');
    assert.equal(picker.style.transform, 'none');
    assert.equal(picker.style.left, '368px');
    assert.equal(picker.style.top, '208px');
    assert.equal(parseFloat(picker.style.left) + 320 <= 700, true, 'picker stays left of the visitor profile');
});

test('visitor messages open the floating chat on other admin pages', () => {
    const layoutPath = path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'layout', 'app.blade.php');
    const layout = fs.readFileSync(layoutPath, 'utf8');
    const start = layout.indexOf('function subscribeToDockChat(chat) {');
    const end = layout.indexOf('function registerDockChat(chat, openOnChatPage = false) {', start);
    assert.notEqual(start, -1, 'dock subscription handler must exist');
    assert.notEqual(end, -1, 'dock registration handler must follow subscription handler');

    let messageHandler;
    let openedChatId = null;
    const chat = { id: '42', name: 'Visitor-42', unread: 0, preview: '' };
    const context = {
        chat,
        dockChannels: new Set(),
        pusher: {
            subscribe: () => ({
                bind: (eventName, handler) => {
                    if (eventName === 'new-message') {
                        messageHandler = handler;
                    }
                }
            })
        },
        dockChats: new Map([[chat.id, chat]]),
        floatingChatId: null,
        dockWindow: { classList: { contains: () => false } },
        window: {
            location: {
                pathname: '/admin/dashboard',
                origin: 'http://localhost',
                search: ''
            }
        },
        URL,
        URLSearchParams,
        chatRoute: '/admin/chat',
        saveDockChats() {},
        renderDock() {},
        notifySound: { currentTime: 0, play: () => Promise.resolve() },
        showChatToast() {},
        openFloatingChat(chatId) {
            openedChatId = chatId;
        }
    };

    vm.runInNewContext(`${layout.slice(start, end)}subscribeToDockChat(chat);`, context);
    messageHandler({
        id: 100,
        chat_id: 42,
        role: 3,
        sender_type: 'visitor',
        message: 'hello'
    });

    assert.equal(openedChatId, chat.id);
});

test('a visitor message restores a closed dock chat and opens its mini-chat', () => {
    const layoutPath = path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'layout', 'app.blade.php');
    const layout = fs.readFileSync(layoutPath, 'utf8');
    const start = layout.indexOf('function handleVisitorMessageNotification(data) {');
    const end = layout.indexOf('\n            try {', start);
    assert.notEqual(start, -1, 'global visitor message handler must exist');
    assert.notEqual(end, -1, 'visitor message handler must finish before channel bindings');

    const chat = { id: '42', name: 'Visitor-42', unread: 0, preview: '' };
    const dockChats = new Map();
    let openedChatId = null;
    const context = {
        currentUserRole: 2,
        currentUserId: 7,
        dockChats,
        window: {
            location: {
                pathname: '/admin/dashboard',
                origin: 'http://localhost',
                search: ''
            }
        },
        URL,
        URLSearchParams,
        chatRoute: '/admin/chat',
        subscribeToDockChat() {},
        registerDockChat(registeredChat) {
            dockChats.set(String(registeredChat.id), registeredChat);
        },
        openFloatingChat(chatId) {
            openedChatId = chatId;
        },
        notifySound: { currentTime: 0, play: () => Promise.resolve() },
        showChatToast() {},
        saveDockChats() {},
        renderDock() {}
    };

    vm.runInNewContext(`${layout.slice(start, end)}handleVisitorMessageNotification({
        chat: { id: 42 },
        visitor: { id: 31 },
        message: { id: 100, message: 'hello' },
        user_ids: [7]
    });`, context);

    assert.equal(dockChats.has('42'), true, 'chat should be restored to an empty dock');
    assert.equal(openedChatId, '42', 'visitor chat should open on the current page');
});

test('admin replies appear immediately without waiting for Pusher', async () => {
    const chatViewPath = path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat.blade.php');
    const chatView = fs.readFileSync(chatViewPath, 'utf8');
    const start = chatView.indexOf('function sendMessage(form, input, message) {');
    const end = chatView.indexOf('\ndocument.addEventListener(\'DOMContentLoaded\'', start);
    assert.notEqual(start, -1, 'admin send handler must exist');
    assert.notEqual(end, -1, 'admin send handler must precede page initialization');

    const appendedMessages = [];
    const status = { textContent: 'previous error' };
    const sendButton = { disabled: false };
    const input = {
        value: 'Admin reply',
        disabled: false,
        focus() {}
    };
    const form = {
        dataset: { chatId: '42' },
        querySelector: () => sendButton,
        closest: () => ({ querySelector: () => status })
    };
    const context = {
        fetch: async () => ({
            ok: true,
            json: async () => ({
                status: 'sent',
                message: {
                    id: 101,
                    message: 'Admin reply',
                    role: 1,
                    formatted_created_at: '10:00 AM, Oct 20, 2026',
                    user: { id: 7, name: 'Admin', image: null }
                }
            })
        }),
        document: {
            querySelector: () => ({ content: 'csrf-token' })
        },
        window: { chatId: '42' },
        messageAlreadyRendered: () => false,
        appendMessage: (...args) => appendedMessages.push(args),
        console: { error() {} }
    };

    await vm.runInNewContext(
        `${chatView.slice(start, end)}sendMessage(form, input, 'Admin reply');`,
        { ...context, form, input }
    );

    assert.equal(appendedMessages.length, 1, 'successful reply should be rendered locally');
    assert.equal(appendedMessages[0][0], 'Admin reply');
    assert.equal(appendedMessages[0][5], 101, 'message id should support Pusher deduplication');
    assert.equal(input.value, '');
    assert.equal(status.textContent, '');
    assert.equal(sendButton.disabled, false);
});

test('admin send failures are shown and preserve the draft', async () => {
    const chatViewPath = path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'chat.blade.php');
    const chatView = fs.readFileSync(chatViewPath, 'utf8');
    const start = chatView.indexOf('function sendMessage(form, input, message) {');
    const end = chatView.indexOf('\ndocument.addEventListener(\'DOMContentLoaded\'', start);
    const status = { textContent: '' };
    const input = {
        value: 'Unsent reply',
        disabled: false,
        focus() {}
    };
    const sendButton = { disabled: false };
    const form = {
        dataset: { chatId: '42' },
        querySelector: () => sendButton,
        closest: () => ({ querySelector: () => status })
    };
    const context = {
        fetch: async () => ({
            ok: false,
            json: async () => ({ message: 'AI is generating a response.' })
        }),
        document: {
            querySelector: () => ({ content: 'csrf-token' })
        },
        window: { chatId: '42' },
        console: { error() {} }
    };

    await vm.runInNewContext(
        `${chatView.slice(start, end)}sendMessage(form, input, 'Unsent reply');`,
        { ...context, form, input }
    );

    assert.equal(status.textContent, 'AI is generating a response.');
    assert.equal(input.value, 'Unsent reply', 'failed send should preserve the draft');
    assert.equal(input.disabled, false);
    assert.equal(sendButton.disabled, false);
});

test('floating chat replies render locally when Pusher does not echo them', async () => {
    const layoutPath = path.join(__dirname, '..', '..', 'resources', 'views', 'admin', 'layout', 'app.blade.php');
    const layout = fs.readFileSync(layoutPath, 'utf8');
    const start = layout.indexOf('async function sendFloatingMessage(form, chatId) {');
    const end = layout.indexOf('\n            async function openFloatingChat', start);
    assert.notEqual(start, -1, 'floating-chat send handler must exist');
    assert.notEqual(end, -1, 'floating-chat send handler must precede chat loading');

    const renderedMessages = [];
    const input = { value: 'Reply from minimized chat' };
    const sendButton = { disabled: false };
    const form = {
        querySelector(selector) {
            return selector === '.message-input' ? input : sendButton;
        }
    };
    const context = {
        floatingChatId: '42',
        chatSendRoute: '/admin/send-message',
        dockWindow: { classList: { contains: () => true } },
        dockWindowError: { hidden: false, textContent: 'previous error' },
        fetch: async () => ({
            ok: true,
            json: async () => ({
                status: 'sent',
                message: { id: 102, message: 'Reply from minimized chat', role: 1 }
            })
        }),
        document: {
            querySelector: () => ({ content: 'csrf-token' })
        },
        renderFloatingMessage: message => renderedMessages.push(message),
        showFloatingError() {},
        console: { error() {} }
    };

    await vm.runInNewContext(
        `${layout.slice(start, end)}sendFloatingMessage(form, '42');`,
        { ...context, form }
    );

    assert.equal(renderedMessages.length, 1, 'successful dock reply should render in the open mini-chat');
    assert.equal(renderedMessages[0].id, 102);
    assert.equal(input.value, '');
    assert.equal(context.dockWindowError.hidden, true);
    assert.equal(sendButton.disabled, false);
});
