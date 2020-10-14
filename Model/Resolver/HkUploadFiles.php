<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare( strict_types=1 );

namespace HuyKon\MageGraphQlUploader\Model\Resolver;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Filesystem;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;

class HkUploadFiles implements ResolverInterface {

	private $fileSystem;

	public function __construct(
		Filesystem $fileSystem
	) {
		$this->fileSystem = $fileSystem;
	}

	/**
	 * @inheritdoc
	 */
	public function resolve(
		Field $field,
		$context,
		ResolveInfo $info,
		array $value = null,
		array $args = null
	) {
		if ( empty( $args['input'] ) || ! is_array( $args['input'] ) || ! count( $args['input'] ) ) {
			throw new GraphQlInputException( __( 'You must specify your input.' ) );
		}
		if ( empty( $args['input'][0]['name'] ) ) {
			throw new GraphQlInputException( __( 'You must specify your "file name".' ) );
		}

		if ( empty( $args['input'][0]['base64_encoded_file'] ) ) {
			throw new GraphQlInputException( __( 'You must specify your "file base64 encode".' ) );
		}

		try {
			$mediaPath     = $this->fileSystem->getDirectoryRead( DirectoryList::MEDIA )->getAbsolutePath();
			$originalPath  = 'gql-folder/files/';
			$mediaFullPath = $mediaPath . $originalPath;
			if ( ! file_exists( $mediaFullPath ) ) {
				mkdir( $mediaFullPath, 0775, true );
			}

			$arrayReturn = [ 'items' => null ];
			foreach ( $args['input'] as $input ) {
				$fileName        = rand() . time() . '_' . $input['name'];
				$base64FileArray = explode( ',', $input['base64_encoded_file'] );

				$fileContent = base64_decode( $base64FileArray[1] );
				$savedFile   = fopen( $mediaFullPath . $fileName, "wb" );
				fwrite( $savedFile, $fileContent );
				fclose( $savedFile );
				$arrayReturn['items'][] = [
					'name'       => $fileName,
					'full_path'  => $mediaFullPath . $fileName,
					'quote_path' => $originalPath . $fileName,
					'order_path' => $originalPath . $fileName,
					'secret_key' => substr( md5( file_get_contents( $mediaFullPath . $fileName ) ), 0, 20 )
				];
			}

			return $arrayReturn;

		}
		catch ( InputException $e ) {
			throw new GraphQlInputException( __( $e->getMessage() ) );
		}
	}
}