/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { arrowRight } from '@wordpress/icons';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import type { Message } from './context/conversation-provider';

/**
 * Internal dependencies
 */
import {
	UserMessage,
	AssistantMessage,
	ToolCall,
	PendingAssistantMessage,
	FeatureTool,
} from './components/chat-message';
import {
	ConversationProvider,
	useConversation,
} from './context/conversation-provider';

const ChatAppContent = () => {
	const { messages, addMessage, clearMessages } = useConversation();
	const [ inputValue, setInputValue ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( false );

	const handleSendMessage = async () => {
		if ( ! inputValue.trim() || isLoading ) {
			return;
		}

		setInputValue( '' );
		setIsLoading( true );

		try {
			const response = await apiFetch< { messages: Message[] } >( {
				path: '/wp/v2/demo-chat',
				method: 'POST',
				data: {
					message: inputValue,
				},
			} );

			if ( response.messages ) {
				for ( const message of response.messages ) {
					addMessage( message );
				}
			}
		} catch ( error ) {
			// Handle error appropriately
			console.error( 'Failed to get response:', error );
		} finally {
			setIsLoading( false );
		}
	};

	return (
		<div className="chat-container">
			<div className="chat-header">
				<h2>Demo AI Assistant</h2>
				<Button
					className="chat-header-clear"
					variant="tertiary"
					onClick={ clearMessages }
				>
					Clear
				</Button>
			</div>
			<div className="chat-body">
				<div className="chat-messages">
					{ messages.map( ( message, index ) => {
						switch ( message.role ) {
							case 'user':
								return (
									<UserMessage
										key={ index }
										text={ message.content }
									/>
								);
							case 'tool':
								return (
									<FeatureTool
										key={ index }
										message={ message }
									/>
								);
							default:
								return (
									<AssistantMessage
										key={ index }
										message={ message }
									/>
								);
						}
					} ) }
					{ isLoading && <PendingAssistantMessage /> }
				</div>
				<div className="chat-input">
					<textarea
						className="chat-input-textarea"
						value={ inputValue }
						onChange={ ( e ) => {
							setInputValue( e.target.value );
						} }
						placeholder="Type your message..."
						onKeyDown={ ( e ) => {
							if ( e.key === 'Enter' && ! e.shiftKey ) {
								e.preventDefault();
								handleSendMessage();
							}
						} }
					/>
					<Button
						className="chat-input-submit"
						onClick={ handleSendMessage }
						disabled={ ! inputValue.trim() || isLoading }
						icon={ isLoading ? null : arrowRight }
					>
						{ isLoading ? (
							<Spinner />
						) : (
							<span className="screen-reader-text">Send</span>
						) }
					</Button>
				</div>
			</div>
		</div>
	);
};

export const ChatApp = () => {
	return (
		<ConversationProvider>
			<ChatAppContent />
		</ConversationProvider>
	);
};
