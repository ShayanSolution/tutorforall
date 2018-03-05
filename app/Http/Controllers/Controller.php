<?php //app/Http/Controllers/Controller.php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use League\Fractal\Manager;


/**
 * @SWG\Swagger(
 *     schemes={"http","https"},
 *     host="tutor4all-api.shayansolutions.com",
 *     basePath="/",
 *     @SWG\Info(
 *         version="1.0.0",
 *         title="Tutor4all API",
 *         description="This is tutor4all api",
 *         termsOfService="",
 *         @SWG\Contact(
 *             email="marslanali@gmail.com"
 *         ),
 *         @SWG\License(
 *             name="Private License",
 *             url="#"
 *         )
 *     )
 * )
 */

class Controller extends BaseController
{
    use ResponseTrait;

    /**
     * Constructor
     *
     * @param Manager|null $fractal
     */
    public function __construct(Manager $fractal = null)
    {
        $fractal = $fractal === null ? new Manager() : $fractal;
        $this->setFractal($fractal);
    }

    /**
     * Validate HTTP request against the rules
     *
     * @param Request $request
     * @param array $rules
     * @return bool|array
     */
    protected function validateRequest(Request $request, array $rules)
    {
        // Perform Validation
        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $errorMessages = $validator->errors()->messages();

            // crete error message by using key and value
            foreach ($errorMessages as $key => $value) {
                $errorMessages[$key] = $value[0];
            }

            return $errorMessages;
        }

        return true;
    }
}
