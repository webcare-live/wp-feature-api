/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import {
	Button,
	TextControl,
	Card,
	CardBody,
	CardHeader,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

interface Message {
	id: number;
	text: string;
	isUser: boolean;
}

export const ChatApp = () => {
	const [ messages, setMessages ] = useState< Message[] >( [] );
	const [ inputValue, setInputValue ] = useState( '' );

	const handleSendMessage = () => {
		if ( ! inputValue.trim() ) {
			return;
		}

		const newMessage: Message = {
			id: Date.now(),
			text: inputValue,
			isUser: true,
		};

		setMessages( [ ...messages, newMessage ] );
		setInputValue( '' );

		// TODO: Add API call to get AI response
		// For now, we'll simulate a response
		setTimeout( () => {
			const aiResponse: Message = {
				id: Date.now(),
				text: 'This is a simulated AI response. The actual API integration will be added later.',
				isUser: false,
			};
			setMessages( ( prev ) => [ ...prev, aiResponse ] );
		}, 1000 );
	};

	return (
		<Card className="card-container">
			<CardHeader>
				<h2>AI Chat Assistant</h2>
			</CardHeader>
			<CardBody>
				<div className="wp-feature-api-demo-messages">
					{ messages.map( ( message ) => (
						<div
							key={ message.id }
							className={ `wp-feature-api-demo-message ${
								message.isUser ? 'user' : 'ai'
							}` }
						>
							{ message.text }
						</div>
					) ) }
				</div>
				<div className="wp-feature-api-demo-input">
					<TextControl
						value={ inputValue }
						onChange={ setInputValue }
						placeholder="Type your message..."
						onKeyDown={ ( e ) => {
							if ( e.key === 'Enter' ) {
								handleSendMessage();
							}
						} }
					/>
					<Button
						onClick={ handleSendMessage }
						disabled={ ! inputValue.trim() }
					>
						Send
					</Button>
				</div>
			</CardBody>
		</Card>
	);
};
